<?php
namespace models\languageforge\lexicon\commands;

use models\shared\commands\UploadResponse;
use models\shared\commands\MediaResult;
use models\shared\commands\ErrorResult;
use models\languageforge\lexicon\LexEntryModel;
use models\languageforge\LfProjectModel;

class LexUploadCommands
{

    /**
     * Upload an audio file
     *
     * @param string $projectId
     * @param string $mediaType
     * @param string $tmpFilePath
     * @throws \Exception
     * @return \models\shared\commands\UploadResponse
     */
    public static function uploadAudioFile($projectId, $mediaType, $tmpFilePath)
    {
        if ($mediaType != 'entry-audio') {
            throw new \Exception("Unsupported upload type.");
        }
        if (! $tmpFilePath) {
            throw new \Exception("Upload controller did not move the uploaded file.");
        }

        $entryId = $_POST['entryId'];
        $file = $_FILES['file'];
        $fileName = $file['name'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $tmpFilePath);
        finfo_close($finfo);

        // replace special characters with _
        $search = array(
            '/',
            '\\',
            '?',
            '%',
            '*',
            ':',
            '|',
            '"',
            '<',
            '>'
        );
        $fileName = str_replace($search, '_', $fileName);

        $fileExt = (false === $pos = strrpos($fileName, '.')) ? '' : substr($fileName, $pos);

        $allowedTypes = array(
            "audio/mpeg",
            "audio/mp3"
        );
        $allowedExtensions = array(
            ".mp3"
        );

        $response = new UploadResponse();
        if (in_array(strtolower($fileType), $allowedTypes) && in_array(strtolower($fileExt), $allowedExtensions)) {

            // make the folders if they don't exist
            $project = new LfProjectModel($projectId);
            $folderPath = $project->getAssetsFolderPath() . '/audio';
            if (! file_exists($folderPath) and ! is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            // cleanup previous files of any allowed extension
            self::cleanupFiles($folderPath, $entryId, $allowedExtensions);

            // move uploaded file from tmp location to assets
            $filePath = self::mediaFilePath($folderPath, $entryId, $fileName);
            $moveOk = rename($tmpFilePath, $filePath);

            // update database with file location
            $entry = new LexEntryModel($project, $entryId);
            $entry->audioFileName = '';
            if ($moveOk) {
                $entry->audioFileName = $fileName;
            }
            $entry->write();

            // construct server response
            if ($moveOk && $tmpFilePath) {
                $data = new MediaResult();
                $data->path = $project->getAssetsPath();
                $data->fileName = $fileName;
                $response->result = true;
            } else {
                $data = new ErrorResult();
                $data->errorType = 'UserMessage';
                $data->errorMessage = "$fileName could not be saved to the right location. Contact your Site Administrator.";
                $response->result = false;
            }
        } else {
            $allowedExtensionsStr = implode(", ", $allowedExtensions);
            $data = new ErrorResult();
            $data->errorType = 'UserMessage';
            if (count($allowedExtensions) < 1) {
                $data->errorMessage = "$fileName is not an allowed audio file. No audio file formats are currently enabled, contact your Site Administrator.";
            } elseif (count($allowedExtensions) == 1) {
                $data->errorMessage = "$fileName is not an allowed audio file. Ensure the file is an $allowedExtensionsStr.";
            } else {
                $data->errorMessage = "$fileName is not an allowed audio file. Ensure the file is one of the following types: $allowedExtensionsStr.";
            }
            $response->result = false;
        }

        $response->data = $data;
        return $response;
    }

    /**
     * Upload an image file
     *
     * @param string $projectId
     * @param string $mediaType
     * @param string $tmpFilePath
     * @throws \Exception
     * @return \models\shared\commands\UploadResponse
     */
    public static function uploadImageFile($projectId, $mediaType, $tmpFilePath)
    {
        if ($mediaType != 'sense-image') {
            throw new \Exception("Unsupported upload type.");
        }
        if (! $tmpFilePath) {
            throw new \Exception("Upload controller did not move the uploaded file.");
        }

        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileNamePrefix = date("YmdHis");

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $tmpFilePath);
        finfo_close($finfo);

        // replace special characters with _
        $search = array(
            '/',
            '\\',
            '?',
            '%',
            '*',
            ':',
            '|',
            '"',
            '<',
            '>'
        );
        $fileName = str_replace($search, '_', $fileName);

        $fileExt = (false === $pos = strrpos($fileName, '.')) ? '' : substr($fileName, $pos);

        $allowedTypes = array(
            "image/jpeg",
            "image/jpg",
            "image/png"
        );
        $allowedExtensions = array(
            ".jpg",
            ".jpeg",
            ".png"
        );

        $response = new UploadResponse();
        if (in_array(strtolower($fileType), $allowedTypes) && in_array(strtolower($fileExt), $allowedExtensions)) {

            // make the folders if they don't exist
            $project = new LfProjectModel($projectId);
            $folderPath = self::imageFolderPath($project->getAssetsFolderPath());
            if (! file_exists($folderPath) and ! is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            // move uploaded file from tmp location to assets
            $filePath = self::mediaFilePath($folderPath, $fileNamePrefix, $fileName);
            $moveOk = rename($tmpFilePath, $filePath);

            // construct server response
            if ($moveOk && $tmpFilePath) {
                $data = new MediaResult();
                $data->path = self::imageFolderPath($project->getAssetsPath());
                $data->fileName = $fileNamePrefix . '_' . $fileName;
                $response->result = true;
            } else {
                $data = new ErrorResult();
                $data->errorType = 'UserMessage';
                $data->errorMessage = "$fileName could not be saved to the right location. Contact your Site Administrator.";
                $response->result = false;
            }
        } else {
            $allowedExtensionsStr = implode(", ", $allowedExtensions);
            $data = new ErrorResult();
            $data->errorType = 'UserMessage';
            if (count($allowedExtensions) < 1) {
                $data->errorMessage = "$fileName is not an allowed image file. No image file formats are currently enabled, contact your Site Administrator.";
            } elseif (count($allowedExtensions) == 1) {
                $data->errorMessage = "$fileName is not an allowed image file. Ensure the file is an $allowedExtensionsStr.";
            } else {
                $data->errorMessage = "$fileName is not an allowed image file. Ensure the file is one of the following types: $allowedExtensionsStr.";
            }
            $response->result = false;
        }

        $response->data = $data;
        return $response;
    }

    /**
     *
     * @param string $projectId
     * @param string $mediaType, options are 'image'.
     * @param string $fileName
     * @throws \Exception
     * @return \models\shared\commands\UploadResponse
     */
    public static function deleteMediaFile($projectId, $mediaType, $fileName) {
        $response = new UploadResponse();
        $response->result = false;
        $project = new LfProjectModel($projectId);
        switch ($mediaType) {
        	case 'sense-image':
                $folderPath = self::imageFolderPath($project->getAssetsFolderPath());
        	    break;
        	default:
        	    $errorMsg = "Error in function deleteImageFile, unsupported mediaType: $mediaType";
        	    throw new \Exception($errorMsg) ;
                $data = new ErrorResult();
                $data->errorType = 'Exception';
                $data->errorMessage = $errorMsg;
        	    return $response;
        }
        $filePath = $folderPath . '/' . $fileName;
        if (file_exists($filePath) and ! is_dir($filePath)) {
            if (unlink($filePath)) {
                $data = new MediaResult();
                $data->path = self::imageFolderPath($project->getAssetsPath());
                $data->fileName = $fileName;
                $response->result = true;
            } else {
                $data = new ErrorResult();
                $data->errorType = 'UserMessage';
                $data->errorMessage = "$fileName could not be deleted. Contact your Site Administrator.";
            }
            return $response;
        }
        $data = new ErrorResult();
        $data->errorType = 'UserMessage';
        $data->errorMessage = "$fileName does not exist in this project. Contact your Site Administrator.";
        return $response;
    }

    /**
     *
     * @param string $assetsFolderPath
     * @return string
     */
    public static function imageFolderPath($assetsFolderPath)
    {
        return $assetsFolderPath . '/pictures';
    }

    /**
     *
     * @param string $folderPath
     * @param string $fileNamePrefix
     * @param string $originalFileName
     * @return string
     */
    public static function mediaFilePath($folderPath, $fileNamePrefix, $originalFileName)
    {
        return $folderPath . '/' . $fileNamePrefix . '_' . $originalFileName;
    }

    /**
     * cleanup (remove) previous files of any allowed extension for files with the given filename prefix in the given folder path
     *
     * @param string $folderPath
     * @param string $fileNamePrefix
     * @param array $allowedExtensions
     */
    public static function cleanupFiles($folderPath, $fileNamePrefix, $allowedExtensions)
    {
        $cleanupFiles = glob($folderPath . '/' . $fileNamePrefix . '*[' . implode(', ', $allowedExtensions) . ']');
        foreach ($cleanupFiles as $cleanupFile) {
            @unlink($cleanupFile);
        }
    }
}
