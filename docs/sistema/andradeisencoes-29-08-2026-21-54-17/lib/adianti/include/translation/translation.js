Adianti.translation = {
    'en' : {
        'large_file' : 'The file is too large. The upload limit is ',
        'success': 'Success',
        'file_uploaded_successfully' : 'File uploaded successfully'
    },
    'pt' : {
        'large_file' : 'O arquivo é muito grande. O limite de carregamento é ',
        'success': 'Sucesso',
        'file_uploaded_successfully' : 'Arquivo carregado com sucesso'
    },
    'es' : {
        'large_file' : 'El archivo es demasiado grande. El límite de carga es ',
        'success': 'Éxito',
        'file_uploaded_successfully' : 'Archivo cargado con éxito'
    }
}

function _t(message)
{
    return Adianti.translation[Adianti.language][message] ?? message;
}
