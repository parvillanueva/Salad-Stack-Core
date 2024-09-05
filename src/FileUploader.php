<?php

namespace Salad\Core;

class FileUploader
{
    protected $allowedFileTypes;
    protected $maxFileSize;
    protected $uploadDirectory;

    public function __construct($allowedFileTypes = ['image/jpg', 'application/*'], $maxFileSize = 5242880)
    {
        $this->uploadDirectory = "./upload";
        $this->allowedFileTypes = $allowedFileTypes;
        $this->maxFileSize = $maxFileSize;

        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0777, true);
        }
    }

    public function upload($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->getErrorMessage($file['error']);
        }
        if ($file['size'] > $this->maxFileSize) {
            return "File exceeds maximum allowed size of " . ($this->maxFileSize / 1048576) . " MB.";
        }

        $fileName = $this->generateUniqueFileName($file['name']);
        $filePath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'file_path' => $filePath
            ];
        }

        return "Failed to move uploaded file.";
    }

    public function uploadMultiple($files)
    {
        $results = [];
        foreach ($files['name'] as $index => $fileName) {
            $file = [
                'name' => $fileName,
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];

            $results[] = $this->upload($file);
        }

        return $results;
    }

    protected function generateUniqueFileName($originalName)
    {
        $fileInfo = pathinfo($originalName);
        $uniqueName = md5(uniqid(rand(), true)) . '.' . $fileInfo['extension'];
        return $uniqueName;
    }

    protected function getErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
        ];

        return isset($errors[$errorCode]) ? $errors[$errorCode] : "Unknown upload error.";
    }
}
