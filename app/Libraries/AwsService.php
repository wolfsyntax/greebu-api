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

            // $s3 = AWS::createClient('s3');
            $result = $this->client->getObject([
                'Bucket'        => config("filesystems.disks.$driver.bucket"),
                'Key'           => $s3_filename,
            ]);
            // $result = $this->client->getObject([
            //     'Bucket'        => config("filesystems.disks.$driver.bucket"),
            //     'Key'           => $s3_filename,
            // ]);

            return $result['ObjectURL'];
            return $result;
        } catch (AWS\S3\Exception\S3Exception $e) {
            return '';
        }
    }

    public function files($driver = 's3')
    {
        return [];
        return $this->client->listMultipartUploads([
            'Bucket' => config("filesystems.disks.$driver.bucket")
        ])->toArray();
    }
}
