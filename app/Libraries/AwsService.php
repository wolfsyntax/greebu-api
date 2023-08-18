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

    public function put_object_to_aws($s3_filename, $filename, $driver = 's3')
    {
        //$s3 = AWS::createClient('s3');

        try {
            $result = $this->client->putObject([
                'Bucket'        => config("filesystems.disks.$driver.bucket"),
                'Key'           => $s3_filename,
                'SourceFile'    => $filename
            ]);

            // return $result['ObjectURL'];
            return $s3_filename;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return '';
        }
    }

    public function get_aws_object($s3_filename, $driver = 's3')
    {
        try {

            $result = $this->client->getObjectUrl(config("filesystems.disks.$driver.bucket"), $s3_filename);

            return $result;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return $e;
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
            return $e;
        }
    }
}
