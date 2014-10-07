<?php
namespace models\scriptureforge\sfchecks\commands;

use models\shared\commands\UploadResponse;
use models\shared\commands\MediaResult;
use models\shared\commands\ErrorResult;
use models\scriptureforge\SfchecksProjectModel;
use models\TextModel;

class SfchecksUploadCommands
{

    /**
     * Upload a file
     *
     * @param string $projectId
     * @param string $mediaType
     * @param string $tmpFilePath
     * @throws \Exception
     * @return \models\shared\commands\UploadResponse
     */
    public static function uploadFile($projectId, $mediaType, $tmpFilePath)
    {
        if ($mediaType != 'audio') {
            throw new \Exception("Unsupported upload type: $mediaType");
        }
        if (! $tmpFilePath) {
            throw new \Exception("Upload controller did not move the uploaded file.");
        }

        $textId = $_POST['textId'];
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

        // allowed types: documented, observed
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
            $project = new SfchecksProjectModel($projectId);
            $folderPath = $project->getAssetsFolderPath();
            if (! file_exists($folderPath) and ! is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            // cleanup previous files of any allowed extension
            self::cleanupFiles($folderPath, $textId, $allowedExtensions);

            // move uploaded file from tmp location to assets
            $filePath = self::mediaFilePath($folderPath, $textId, $fileName);
            $moveOk = copy($tmpFilePath, $filePath);
            @unlink($tmpFilePath);

            // update database with file location
            $text = new TextModel($project, $textId);
            $text->audioFileName = '';
            if ($moveOk) {
                $text->audioFileName = $fileName;
            }
            $text->write();

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
     *
     * @param string $folderPath
     * @param string $fileNamePrefix
     * @param string $fileName
     * @return string
     */
    public static function mediaFilePath($folderPath, $fileNamePrefix, $fileName)
    {
        return $folderPath . '/' . $fileNamePrefix . '_' . $fileName;
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
