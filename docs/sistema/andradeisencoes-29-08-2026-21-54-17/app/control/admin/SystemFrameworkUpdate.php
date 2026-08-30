<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TForm;
use Adianti\Wrapper\BootstrapDatagridWrapper;

/**
 * SystemFrameworkUpdate
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Lucas Tomasi
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemFrameworkUpdate extends TPage
{
    private static $formName = 'form_SystemFrameworkUpdate';
    private $datagrid_form;  // form listing
    private $loaded;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        BuilderPermissionService::checkPermission();

        // creates a Datagrid
        $this->datagrid_files = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid_files->disableHtmlConversion();
        $this->datagrid_files->style = 'width: 100%';
        $this->datagrid_files->setHeight(320);

        $this->datagrid_sqls = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid_sqls->disableHtmlConversion();
        $this->datagrid_sqls->style = 'width: 100%';
        $this->datagrid_sqls->setHeight(320);

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $column_file = new TDataGridColumn('file', _bt("File"), 'left');
        $column_status = new TDataGridColumn('message', _bt("Message"), 'left','65%');

        $column_sql = new TDataGridColumn('sql', "SQL", 'left', '40%');
        $column_database = new TDataGridColumn('database', _bt("Database"), 'left');
        $column_message = new TDataGridColumn('message', _bt("Message"), 'left','40%');
        
        $this->datagrid_files->addColumn($column_file);
        $this->datagrid_files->addColumn($column_status);

        $this->datagrid_sqls->addColumn($column_database);
        $this->datagrid_sqls->addColumn($column_sql);
        $this->datagrid_sqls->addColumn($column_message);
        
        $this->datagrid_files->createModel();
        $this->datagrid_sqls->createModel();

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        $this->datagrid_form->add('<div class="card-header panel-heading" style=""><div class="card-title panel-title"><div style="width: 100%">'._bt('Structure').'</div></div></div>');
        $this->datagrid_form->add($this->datagrid_files);
        $this->datagrid_form->add('<div class="card-header panel-heading" style=""><div class="card-title panel-title"><div style="width: 100%">'._bt('Database').'</div></div></div>');
        $this->datagrid_form->add($this->datagrid_sqls);

        $button = new TButton('button_onupdate');
        $this->datagrid_form->addField($button);
        
        $button->setAction(new TAction([$this, 'onUpdate'], ['static' => 1]), 'Update');
        $button->setImage('fa:sync-alt');
        $button->addStyleClass('btn-success');

        $ini = AdiantiApplicationConfig::get();

        $infos = new TElement('div');
        $infos->style = 'padding: 10px;';
        $infos->add('<h5>' . _bt('Some important information') . '!</h5>');
        $infos->add('<div>' . _bt('A backup will be performed in your project <b>tmp</b> directory, containing the folders and files replaced during the process') . '</div>');
        // $infos->add("<div>" . _bt('See the complete changelog') . ": <br> <a target=\"_blank\" href=\"" . $ini['builder']['manager_url'] . "/changelogs\">Mad Builder</a></div>");
        
        $panel = new TPanelGroup('System Framework Update');
        $panel->add($infos);
        $panel->add($this->datagrid_form);
        $panel->addFooter($button);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($panel);

        parent::add($container);
    }
    
    public static function onUpdate($param)
    {
        try
        {
            BuilderPermissionService::checkPermission();

            $infos = BuilderService::getInstructionsUpdateLib();

            $msg = '';

            if (! empty($infos['files']))
            {
                $folder = BuilderService::downloadFilesUploadLib();
                
                $folder_name = 'tmp/bkp_update_lib_' . date('Y_m_d_H_i_s');

                mkdir($folder_name);

                if (! is_writable($folder_name))
                {
                    throw new Exception( (string) _bt('Permission denied, could not create the backup folder'));
                }

                // PRIMEIRA PARTE: VALIDAÇÃO DE ARQUIVOS E PERMISSÕES
                foreach($infos['files'] as $file => $fileInfo)
                {
                    // Verificar se o arquivo/pasta de origem existe
                    if (!file_exists("{$folder}{$file}"))
                    {
                        throw new Exception('File not found: ' . $file);
                    }

                    if ($fileInfo['type'] == 'folder')
                    {
                        // Para pastas - Verificações básicas e depois recursivas
                        $sourceFolder = $folder . $file;
                        
                        // Verificar se o destino existe e é realmente um diretório
                        if (file_exists($file) && !is_dir($file)) {
                            throw new Exception('Path exists but is not a directory: ' . $file);
                        }
                        
                        // Verificar permissão no diretório pai (para criar/modificar)
                        if (!is_writable(dirname($file))) {
                            throw new Exception('Do not have permission to modify parent of: ' . $file);
                        }
                        
                        // Verificação recursiva para o destino (se existir)
                        if (file_exists($file)) {
                            $permissionErrors = [];
                            if (!self::checkDirectoryPermissions($file, $permissionErrors)) {
                                throw new Exception('Insufficient permissions in target directory: ' . 
                                                "\n" . implode("\n", $permissionErrors));
                            }
                        }
                        
                        // Verificação recursiva para a origem
                        $permissionErrors = [];
                        if (!is_readable($sourceFolder) || !self::checkDirectoryPermissions($sourceFolder, $permissionErrors)) {
                            throw new Exception('Insufficient permissions in source directory: ' . 
                                            "\n" . implode("\n", $permissionErrors));
                        }
                    }
                    else
                    {
                        // Para arquivos - Verificações simplificadas
                        $sourceFile = $folder . $file;
                        
                        // Verificar se o destino é um arquivo (não uma pasta)
                        if (file_exists($file) && is_dir($file)) {
                            throw new Exception('Path exists but is a directory, not a file: ' . $file);
                        }
                        
                        // Verificar permissão de escrita no arquivo ou pasta pai
                        if (file_exists($file)) {
                            if (!is_writable($file)) {
                                throw new Exception('Do not have write permission on file: ' . $file);
                            }
                        } else if (!is_writable(dirname($file))) {
                            throw new Exception('Cannot create file at: ' . $file);
                        }
                        
                        // Verificar permissão de leitura no arquivo de origem
                        if (!is_readable($sourceFile)) {
                            throw new Exception('Cannot read source file: ' . $sourceFile);
                        }
                    }
                }
                
                // Criar diretório para backups se não existir
                if (!file_exists($folder_name)) {
                    mkdir($folder_name, 0755, true);
                }

                // SEGUNDA PARTE: EXECUÇÃO DAS OPERAÇÕES
                foreach($infos['files'] as $file => $fileInfo)
                {
                    if ($fileInfo['type'] == 'folder')
                    {
                        // Fazer backup preservando a estrutura de diretórios (similar ao --parents)
                        self::copyWithParents($file, $folder_name);
                        
                        // Criar um diretório temporário com nome único
                        $temp_dir = dirname($file) . DIRECTORY_SEPARATOR . 'temp_' . md5(time() . mt_rand());
                        if (!file_exists($temp_dir)) {
                            mkdir($temp_dir, 0755, true);
                        }
                        
                        try {
                            // Copiar conteúdo novo para diretório temporário
                            self::recursiveCopy($folder . $file, $temp_dir);
                            
                            // Limpar o conteúdo do diretório original (sem excluir o diretório em si)
                            self::clearDirectory($file);
                            
                            // Copiar do temporário para o original
                            self::recursiveCopy($temp_dir, $file);
                            
                            // Remover diretório temporário
                            self::clearDirectory($temp_dir);
                            rmdir($temp_dir);
                            
                        } catch (Exception $e) {
                            // Em caso de erro, restaurar do backup
                            self::clearDirectory($file);
                            self::recursiveCopy($folder_name . DIRECTORY_SEPARATOR . $file, $file);
                            
                            // Limpar diretório temporário se existir
                            if (file_exists($temp_dir)) {
                                self::clearDirectory($temp_dir);
                                rmdir($temp_dir);
                            }
                            
                            throw new Exception('Failed to update directory ' . $file . ': ' . $e->getMessage());
                        }
                    }
                    else
                    {
                        // Para arquivos simples: backup e substituição
                        // Fazer backup preservando a estrutura de diretórios
                        self::copyWithParents($file, $folder_name);
                        
                        // Substituir o arquivo
                        copy($folder . $file, $file);
                    }
                }

                exec("rm -r {$folder}");
                
                $msg .= '-' . _bt('Files changed') . '<br/>';
                $msg .= '-'. _bt('Backup created') . ': ' . $folder_name . '<br/>';
            }
            
            if (! empty($infos['sqls']))
            {
                $msg .= '-' . _bt('SQL changes') . '<br/>';
                foreach($infos['sqls'] as $key => $sql)
                {
                    try
                    {
                        $msg .= $sql['message'] . ': <b>';
                        TTransaction::openFake($param['databases'][$key]??$sql['schema']);
                        
                        // If insert permission validate controller already exists
                        if ($sql['type'] == 'INSERT_PERMISSION' && ! empty($sql['controller']))
                        {
                            $count = SystemProgram::where('controller', '=', $sql['controller'])->count();

                            if ($count > 0)
                            {
                                $msg .= 'Program: ' . $sql['controller'] . ' already exists';
                                $msg .=  '</b><br/>';
                                TTransaction::close();
                                continue;
                            }
                        }

                        $type = TTransaction::getDatabaseInfo()['type'];
                        $commandSQL = $sql['command'][$type]??null;
                        $conn = TTransaction::get();

                        if (is_array($commandSQL))
                        {
                            foreach($commandSQL as $cSql)
                            {
                                try{
                                    $conn->query($cSql);
                                }
                                catch(Exception $e)
                                {
                                    // TTransaction::rollback();
                                    $erroMessage = $e->getMessage();

                                    if(!preg_match('/exists|existe|duplicate|duplicada/i', $erroMessage))
                                    {
                                        $msg .= $e->getMessage();
                                    }
                                }
                                finally
                                {
                                    $msg .=  '</b><br/>';
                                }
                            }
                        }
                        else
                        {
                            try{
                                $conn->query($commandSQL);
                            }
                            catch(Exception $e)
                            {
                                // TTransaction::rollback();
                                $erroMessage = $e->getMessage();

                                if(!preg_match('/exists|existe|duplicate|duplicada/i', $erroMessage))
                                {
                                    $msg .= $e->getMessage();
                                }
                            }
                            finally
                            {
                                $msg .=  '</b><br/>';
                            }
                        }

                        $msg .= _bt('Success');
                        
                        TTransaction::close();
                    }
                    catch(Exception $e)
                    {
                        // TTransaction::rollback();
                        $erroMessage = $e->getMessage();

                        if(!preg_match('/exists|existe|duplicate|duplicada/i', $erroMessage))
                        {
                            $msg .= $e->getMessage();
                        }
                    }
                    finally
                    {
                        $msg .=  '</b><br/>';
                    }
                }
            }

            ob_start();
            BuilderMenuUpdate::onUpdateMenu([]);
            ob_end_clean();

            new TMessage('info', $msg , new TAction(['LoginForm', 'reloadPermissions'], ['static' => 1]), _bt('Success'));
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function checkDirectoryPermissions($dir, &$errors = []) {
        if (!is_readable($dir)) {
            $errors[] = "Cannot read directory: $dir";
            return false;
        }
        
        if (!is_writable($dir)) {
            $errors[] = "Cannot write to directory: $dir";
            return false;
        }
        
        $allPermissionsOK = true;
        
        // Verificar cada arquivo e pasta dentro do diretório
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($items as $item) {
            $path = $item->getRealPath();
            
            if ($item->isDir()) {
                // Verificar permissões na pasta
                if (!is_readable($path)) {
                    $errors[] = "Cannot read subdirectory: $path";
                    $allPermissionsOK = false;
                }
                
                if (!is_writable($path)) {
                    $errors[] = "Cannot write to subdirectory: $path";
                    $allPermissionsOK = false;
                }
            } else {
                // Verificar permissões no arquivo
                if (!is_readable($path)) {
                    $errors[] = "Cannot read file: $path";
                    $allPermissionsOK = false;
                }
                
                if (!is_writable($path)) {
                    $errors[] = "Cannot write to file: $path";
                    $allPermissionsOK = false;
                }
            }
        }
        
        return $allPermissionsOK;
    }

     // Funções auxiliares para manipulação de arquivos e diretórios
    private static  function recursiveCopy($src, $dst) {
        // Criar o diretório de destino se não existir
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        // Abrir o diretório de origem
        $dir = opendir($src);
        
        // Copiar cada arquivo/pasta
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcPath = $src . DIRECTORY_SEPARATOR . $file;
                $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
                
                if (is_dir($srcPath)) {
                    // Recursivamente copiar subdiretórios
                    self::recursiveCopy($srcPath, $dstPath);
                } else {
                    // Copiar arquivo
                    copy($srcPath, $dstPath);
                }
            }
        }
        
        closedir($dir);
        return true;
    }

    private static function copyWithParents($file, $destDir) {
        // Criar diretório pai no destino se necessário
        $parentDir = dirname($file);
        $targetDir = $destDir . DIRECTORY_SEPARATOR . $parentDir;
        
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Copiar o arquivo/pasta
        if (is_dir($file)) {
            self::recursiveCopy($file, $destDir . DIRECTORY_SEPARATOR . $file);
        } else {
            copy($file, $destDir . DIRECTORY_SEPARATOR . $file);
        }
    }
    
    private static function clearDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($objects as $object) {
            if ($object->isDir()) {
                rmdir($object->getRealPath());
            } else {
                unlink($object->getRealPath());
            }
        }
        
        return true;
    }

    /**
     * Load the datagrid with data
     */
    public function onReload()
    {
        try
        {
            $diffs = BuilderService::getInstructionsUpdateLib();

            $files = $diffs['files'];
            $sqls = $diffs['sqls'];

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $key => $filter) 
                {
                    if($key == 'file')
                    {
                        $files = array_filter(
                            $files,
                            function($file) use ($filter) {
                                return strpos( strtolower($file['file']??''), strtolower($filter) ) !== FALSE;
                            }
                        );
                    }
                    
                    if($key == 'message')
                    {
                        $files = array_filter(
                            $files,
                            function($file) use ($filter) {
                                return $file['message'] == $filter;
                            }
                        );
                    }
                }
            }

            $this->datagrid_files->clear();

            if ($files)
            {
                foreach ($files as $key => $file)
                {
                    $object = new stdClass;
                    $object->message = $file['message'];
                    $object->file   = $file['file'];

                    $this->datagrid_files->addItem($object);
                }
            }

            $this->datagrid_sqls->clear();

            if ($sqls)
            {
                $databases = $this->getDatabases();

                foreach ($sqls as $key => $sql)
                {
                    $sqlCommand = $sql['command']['sqlite']??'';
                    $sqlCommand = is_array($sqlCommand) ? implode('<br/><br/>', $sqlCommand) : $sqlCommand;

                    $object = new stdClass;
                    $object->sql = $sqlCommand;
                    $object->message = $sql['message'];

                    $combo = new TCombo('databases[]');
                    $combo->addItems($databases);
                    $combo->setValue($sql['schema']??'');
                    $combo->setDefaultOption(false);
                    $combo->setSize('100%');

                    $object->database = $combo;

                    $this->datagrid_form->addField($combo);
                    $this->datagrid_sqls->addItem($object);
                }
            }

            $this->loaded = true;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    private function getDatabases()
    {
        foreach (new DirectoryIterator('app/config') as $file)
        {
            $connection = str_replace(['.ini','.php'], ['',''], $file->getFilename());

            if (in_array($connection, ['application', 'install', 'installed', 'framework_hashes']))
            {
                continue;
            }

            if ($file->isFile() && in_array($file->getExtension(), ['ini', 'php']))
            {
                $list[ $connection ] = $connection;
            }
        }

        natcasesort($list);

        return $list;
    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        if (!$this->loaded)
        {
            $this->onReload();
        }

        parent::show();
    }
}