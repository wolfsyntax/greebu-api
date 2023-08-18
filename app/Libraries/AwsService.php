<?php

namespace App\Libraries;

use AWS;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AwsService
{

    protected $client;

    public function __construct()
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);
    }

    public function put_object_to_aws($s3_filename, $filename, $signed = false)
    {
        //$s3 = AWS::createClient('s3');

        try {
            $result = $this->client->putObject([
                'Bucket'        => config($signed ? "filesystems.disks.s3priv.bucket" : "filesystems.disks.s3.bucket"),
                'Key'           => $s3_filename,
                // 'SourceFile'    => $filename
            ]);

            // return $result['ObjectURL'];
            return $s3_filename;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return '';
        }
    }

    public function get_aws_object($s3_filename, $signed = false)
    {
        try {
            if ($signed) {
                $cmd = $this->client->getCommand('GetObject', [
                    'Bucket' => config($signed ? "filesystems.disks.s3priv.bucket" : "filesystems.disks.s3.bucket"),
                    'Key' => $s3_filename
                ]);

                $request = $this->client->createPresignedRequest($cmd, '+60 minutes');
                $result = (string) $request->getUri();
            } else {
                $result = $this->client->getObjectUrl(config("filesystems.disks.s3.bucket"), $s3_filename);
            }

            return $result;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return '';
        }
    }

    public function delete_aws_object($s3_filename, $driver = 's3')
    {
        try {

            $result = $this->client->deleteObject([
                'Bucket' => config("filesystems.disks.$driver.bucket"),
                'Key' => $s3_filename
            ]);

            return $result;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return '';
        }
    }

    public function check_aws_object($s3_filename, $driver = 's3')
    {
        try {
            return $this->client->doesObjectExist(config("filesystems.disks.$driver.bucket"), $s3_filename);
        } catch (AWS\S3\Exception\S3Exception $e) {
            return false;
        }
    }
}
