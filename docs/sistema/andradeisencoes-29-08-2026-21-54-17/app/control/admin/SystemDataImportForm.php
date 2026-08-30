<?php

class SystemDataImportForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_BuilderDataImport';

    use Adianti\Base\AdiantiFileSaveTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle(_t('Import data'));

        $arquivo = new TFile('arquivo');
        $destination_table = new TCombo('destination_table[]');
        $destination_field = new TCombo('destination_field[]');
        $origem_field = new TCombo('origem_field[]');
        $database = new TCombo('database');
        
        $this->fieldListDataImport = new TFieldList();

        $this->fieldListDataImport->setCloneFunction("ttable_clone_previous_row(this); $('[name=\"destination_table\[\]\"]').off('change.myChange').on('change.myChange', function() {

            System.onChangeImportDataDestinationTable(this);

        });");

        $this->fieldListDataImport->addField(new TLabel(_t('Destination table'), null, '14px', null), $destination_table, ['width' => '33%']);
        $this->fieldListDataImport->addField(new TLabel(_t('Destination field'), null, '14px', null), $destination_field, ['width' => '33%']);
        $this->fieldListDataImport->addField(new TLabel(_t('Origin field (CSV)'), null, '14px', null), $origem_field, ['width' => '100%']);

        $this->fieldListDataImport->width = '100%';
        $this->fieldListDataImport->name = 'fieldListDataImport';

        $this->criteria_fieldListDataImport = new TCriteria();
        $this->default_item_fieldListDataImport = new stdClass();

        $this->form->addField($destination_table);
        $this->form->addField($destination_field);
        $this->form->addField($origem_field);

        $this->fieldListDataImport->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $database->setChangeAction(new TAction([$this,'onChangeDatabase']));

        $arquivo->setCompleteAction(new TAction([$this,'onUpload']));

        $arquivo->enableFileHandling();
        $arquivo->setAllowedExtensions(["csv"]);
        $origem_field->enableSearch();
        $destination_field->enableSearch();
        $database->enableSearch();
        $destination_table->enableSearch();

        $arquivo->setSize('100%');
        $origem_field->setSize('100%');
        $destination_field->setSize('100%');
        $destination_table->setSize('100%');
        $database->setSize('100%');

        $database->addItems(BuilderDatabaseService::listDatabases());
        
        $arquivo->enableDropZone();
        $arquivo->setAllowedExtensions(['csv']);

        $row1 = $this->form->addFields([new TLabel("Arquivo:", null, '14px', null, '100%'),$arquivo]);
        $row1->layout = [' col-sm-12'];

        $row1 = $this->form->addFields([new TLabel(_t('Database'), null, '14px', null, '100%'), $database]);
        $row1->layout = [' col-sm-6'];

        $mapping_container = new BContainer('mapping_container');
        $this->mapping_container = $mapping_container;

        $mapping_container->setTitle(_t('Mapping of columns'), '#333', '18px', '', '#fff');
        $mapping_container->setBorderColor('#c0c0c0');
        $mapping_container->setId('mapping_container');

        $row2 = $mapping_container->addFields([$this->fieldListDataImport]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([$mapping_container]);
        $row3->layout = [' col-sm-12'];

        // create the form actions
        $btnOnImport = $this->form->addAction(_t('Import'), new TAction([$this, 'onImport']), 'fas:database #ffffff');
        $btnOnImport->addStyleClass('btn-primary'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);

        parent::add(TScript::create("
            $('[name=\"destination_table\[\]\"]').on('change.myChange', function() {
                System.onChangeImportDataDestinationTable(this);
            });
        ", false));
    }

    public static function onUpload($param = null) 
    {
        try 
        {
            // Decodifica as informações do arquivo enviadas pelo TFile
            if (!empty($param['arquivo']))
            {
                $file_info = json_decode(urldecode($param['arquivo']), true);

                if (!empty($file_info['fileName']))
                {
                    $filePath  = $file_info['fileName'];
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                    // Aqui, só lidamos com CSV
                    if ($extension !== 'csv') {
                        TForm::sendData(self::$formName, '');
                        throw new Exception(_t('Format not supported. Please upload a CSV file.'));
                    }

                    // Criar cópia do arquivo na pasta tmp com nome único
                    $uniqueFileName = uniqid() . '.' . $extension;
                    $tmpFolder = 'tmp'; // Caminho da pasta tmp (ajuste conforme necessário)
                    $newFilePath = "{$tmpFolder}/{$uniqueFileName}";

                    $file_info['fileName'] = $newFilePath;
                    $file_info['newFile'] = $newFilePath;

                    // Certifique-se de que a pasta tmp existe
                    if (!is_dir($tmpFolder)) {
                        mkdir($tmpFolder, 0777, true);
                    }

                    // Copiar o arquivo para o caminho único
                    if (!copy($filePath, $newFilePath)) {
                        throw new Exception(_t('Error creating a copy of the file in the tmp folder. Please check the permissions of the tmp folder of your project.'));
                    }

                    $object = new stdClass();
                    $object->arquivo = urlencode(json_encode($file_info));

                    TForm::sendData(self::$formName, $object);

                    $filePath  = $file_info['fileName'];

                    // Extrair cabeçalhos do arquivo CSV
                    $headers = self::getHeadersFromCSV($filePath);

                    // Transformar o array simples em associativo para o combo
                    $headersArray = [];
                    foreach ($headers as $h) {
                        if(!empty($h)){
                            $headersArray[$h] = $h;
                        }
                    }

                    TCombo::reload(self::$formName, 'origem_field[]', $headersArray, true, false);
                }
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeDatabase($param = null) 
    {
        try 
        {
            if(!empty($param['key']))
            {
                $tables = BuilderDatabaseService::listTables($param['key']);
                $items = [];
                if($tables)
                {
                    foreach($tables as $key => $table)
                    {   
                        $items[$key] = $table['name'];
                    }
                }

                $tables = json_encode($tables);
                TScript::create("System.tables = {$tables};");

                TCombo::reload(self::$formName, 'destination_table[]', $items, true, false);

            }
            else {
                TScript::create("System.tables = {};");    
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onImport($param = null) 
    {
        try
        {
            $builderTables =BuilderDatabaseService::listTables($param['database']);
            
            if(empty($param['arquivo'])) {
                throw new Exception(_t('No file was uploaded.'));
            }

            $file_info = json_decode(urldecode($param['arquivo']), true);
            $filePath = $file_info['fileName'];

            $tabelas = $param['destination_table'] ?? [];
            $camposDestino = $param['destination_field']   ?? [];
            $camposOrigem = $param['origem_field']    ?? [];

            $mapping = [];
            for ($i = 0; $i < count($tabelas); $i++) {

                $tb = $builderTables[$tabelas[$i]]['name'] ?? null;
                $dbCol = $camposDestino[$i] ?? null;
                $csvCol = $camposOrigem[$i] ?? null;
                if ($tb && $dbCol && $csvCol) 
                {
                    $mapping[$tb][$dbCol] = $csvCol;
                }
            }

            if (empty($mapping)) 
            {
                throw new Exception(_t('No valid mapping defined.'));
            }

            TTransaction::open(MAIN_DATABASE);
            $conn = TTransaction::get();
            $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

            // Desativar constraints
            switch ($driver) 
            {
                case 'mysql':
                    $conn->exec("SET foreign_key_checks=0;");
                    $conn->exec("SET unique_checks=0;");
                    break;
                case 'sqlite':
                    $conn->exec("PRAGMA foreign_keys=OFF;");
                    break;
                case 'pgsql':
                    $conn->exec("SET CONSTRAINTS ALL DEFERRED;");
                    break;
                case 'firebird':
                    // Nada
                    break;
            }

            $insertCommands = [];
            $insertStatements = [];
            foreach ($mapping as $tableName => $mapCols) 
            {
                $cols = array_keys($mapCols);
                if (empty($cols))
                { 
                    continue;
                }

                $colList = implode(',', $cols);
                $placeholders = implode(',', array_fill(0, count($cols), '?'));

                switch ($driver) 
                {
                    case 'mysql':
                        $insertCommand = "REPLACE INTO {$tableName} ({$colList}) VALUES ({$placeholders})";
                        break;
                    case 'sqlite':
                        $insertCommand = "INSERT OR REPLACE INTO {$tableName} ({$colList}) VALUES ({$placeholders})";
                        break;
                    case 'pgsql':
                        $conflictCols = implode(',', $cols);
                        $updateCols = implode(',', array_map(fn($col) => "{$col} = EXCLUDED.{$col}", $cols));
                        $insertCommand = "INSERT INTO {$tableName} ({$colList}) VALUES ({$placeholders}) ON CONFLICT ({$conflictCols}) DO UPDATE SET {$updateCols}";
                        break;
                    case 'firebird':
                        $mergeUpdates = implode(',', array_map(fn($col) => "T.{$col} = SRC.{$col}", $cols));
                        $mergeInsertCols = implode(',', $cols);
                        $mergeInsertVals = implode(',', array_map(fn($col) => "SRC.{$col}", $cols));
                        $insertCommand = "MERGE INTO {$tableName} T USING (SELECT {$placeholders}) AS SRC({$colList}) ON (T.id = SRC.id)
                                          WHEN MATCHED THEN UPDATE SET {$mergeUpdates}
                                          WHEN NOT MATCHED THEN INSERT ({$mergeInsertCols}) VALUES ({$mergeInsertVals})";
                        break;
                    default:
                        throw new Exception(_t('Unsupported driver:').$driver);
                }

                $insertCommands[$tableName] = $insertCommand;
                $insertStatements[$tableName] = $conn->prepare($insertCommand);
            }

            $handle = fopen($filePath, 'r');
            if (!$handle) 
            {
                throw new Exception(_t('CSV file could not be opened.'));
            }

            $headers = fgetcsv($handle);
            $headers = array_map('trim', $headers);

            $rowCount = 0;
            $batchSize = 100;
            $insertCount = 0;

            while (($row = fgetcsv($handle, 0, ';')) !== false) 
            {
                if (count($row) === 1 && strpos($row[0], ',') !== false) 
                {
                    $row = str_getcsv($row[0], ',');
                }

                $row = array_map('trim', $row);

                if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) == 0) 
                {
                    continue;
                }

                $lineAssoc = [];
                foreach ($headers as $index => $head) 
                {
                    $value = $row[$index] ?? null;
                    if ($value !== null && preg_match('/^\d+,\d+$/', $value)) 
                    {
                        $value = str_replace(',', '.', $value);
                    }
                    $lineAssoc[$head] = empty($value) ? null : $value;
                }

                foreach ($mapping as $tableName => $mapCols) 
                {
                    $stmt = $insertStatements[$tableName];
                    $vals = [];
                    foreach ($mapCols as $dbCol => $csvCol) 
                    {
                        $vals[] = $lineAssoc[$csvCol] ?? null;
                    }
                    
                    $stmt->execute($vals);
                }

                $insertCount++;
                $rowCount++;

                if ($insertCount >= $batchSize) {
                    TTransaction::close();
                    TTransaction::open($param['database']);
                    $conn = TTransaction::get();
                    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

                    switch ($driver) {
                        case 'mysql':
                            $conn->exec("SET foreign_key_checks=0;");
                            $conn->exec("SET unique_checks=0;");
                            break;
                        case 'sqlite':
                            $conn->exec("PRAGMA foreign_keys=OFF;");
                            break;
                        case 'pgsql':
                            $conn->exec("SET CONSTRAINTS ALL DEFERRED;");
                            break;
                        case 'firebird':
                            // manter como estava
                            break;
                    }

                    $insertStatements = [];
                    foreach ($mapping as $tableName => $mapCols) 
                    {
                        $insertStatements[$tableName] = $conn->prepare($insertCommands[$tableName]);
                    }

                    $insertCount = 0;
                }
            }

            fclose($handle);

            if ($insertCount > 0) 
            {
                TTransaction::close();
            }

            TTransaction::open(MAIN_DATABASE);
            $conn = TTransaction::get();
            switch ($driver) {
                case 'mysql':
                    $conn->exec("SET foreign_key_checks=1;");
                    $conn->exec("SET unique_checks=1;");
                    break;
                case 'sqlite':
                    $conn->exec("PRAGMA foreign_keys=ON;");
                    break;
                case 'pgsql':
                    $conn->exec("SET CONSTRAINTS ALL IMMEDIATE;");
                    break;
                case 'firebird':
                    // nada
                    break;
            }
            TTransaction::close();

            new TMessage('info', _t('Import completed successfully!<br> Processed records: ^1', $rowCount));
        }
        catch (Exception $e)
        {
            $dados = implode(',', $vals);
            new TMessage('error', "SQL: {$insertCommand}<br><br> Dados: [{$dados}] <br><br> ERRO: ".$e->getMessage());
        }
    }

    public function onShow($param = null)
    {               
        $this->fieldListDataImport->addHeader();
        $this->fieldListDataImport->addDetail($this->default_item_fieldListDataImport);

        $this->fieldListDataImport->addCloneAction(null, 'fas:plus #69aa46', "Clonar");
    } 

    private static function getHeadersFromCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) 
        {
            throw new Exception(_t('CSV file could not be opened.'));
        }

        if (($handle = fopen($filePath, "r")) !== false) 
        {
            $headers = fgetcsv($handle);

            fclose($handle);

            if ($headers !== false) 
            {
                // Cria um array associativo onde a chave e o valor são iguais ao nome da coluna
                return array_combine($headers, $headers);
            }
            else 
            {
                throw new Exception("O arquivo CSV está vazio ou sem cabeçalho.");
            }
        }
    }
}