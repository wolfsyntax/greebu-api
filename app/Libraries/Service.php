<?php

namespace App\Libraries;

// use Aws\S3\S3Client;
use AWS;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
// use Illuminate\Support\Facades\Storage;

class Service
{

    public function __construct()
    {
    }
    public function explode_s3_image_path($img)
    {
        $img = explode('/', $img);
        return $img[count($img) - 1];
    }

    public function remove_object_from_s3($img)
    {
        //if ($this->check_s3_file_path($img)) {
        // $s3 = AWS::createClient('s3');

        // //$img = $this->explode_s3_image_path($img);

        // if ($this->get_s3_object($img)) {
        //     // delete object from aws
        //     try {
        //         $s3->deleteObject([
        //             'Bucket' => env('AWS_PUBLIC_BUCKET'),
        //             'Key' => $img
        //         ]);
        //         return true;
        //     } catch (Aws\S3\Exception\S3Exception $e) {
        //     }
        // }
        // return false;
        //}
    }

    public function put_object_to_s3($s3_filename, $image_file)
    {
        // try {
        //     $s3 = AWS::createClient('s3');

        //     $s3->putObject(array(
        //         'Bucket'        => env('AWS_PUBLIC_BUCKET', 'greebu-staging-public-assets'),
        //         'Key'           => $s3_filename,
        //         'SourceFile'    => $image_file,
        //         //'ACL'           => 'public-read-write',
        //     ));
        // } catch (AWS\S3\Exception\S3Exception $e) {
        //     echo "Error+: " . $e;
        // }
        // return false;
    }

    public function put_object_to_s3priv($s3_filename, $image_file)
    {
        // try {
        //     $s3 = AWS::createClient('s3');
        //     // $s3 = new S3Client([
        //     //     'version' => 'latest',
        //     //     'region'    => 'ap-southeast-1',
        //     // ]);

        //     $s3->putObject(array(
        //         'Bucket'        => env('AWS_PRIVATE_BUCKET', 'greebu-staging-assets'),
        //         'Key'           => $s3_filename,
        //         'SourceFile'    => $image_file,
        //         //'ACL'           => 'public-read-write',
        //     ));

        //     return true;
        // } catch (AWS\S3\Exception\S3Exception $e) {
        // }
        // return false;
    }

    public function check_s3_file_path($img)
    {
        // preg_match('/(s3-|s3\.)?(.*)\.amazonaws\.com/', $img, $matches);
        // if (!empty($matches)) {
        //     return true;
        // }
        // return false;
    }

    public function get_s3_object($img, $access = 'public')
    {
        // try {
        //     // $s3 = AWS::createClient('s3');
        //     // $s3->getObject([
        //     //     'Bucket' => env('AWS_BUCKET', 'greebu-staging-assets'),
        //     //     'Key'   => $img,
        //     // ]);
        //     $url = Storage::disk('s3')->url($img);

        //     if ($access === 'private') {
        //         $s3Client = new S3Client([
        //             //'profile' => 'default',
        //             'region' => 'ap-southeast-1',
        //             'version' => '2006-03-01',
        //         ]);

        //         $cmd = $s3Client->getCommand('GetObject', [
        //             'Bucket' => env('AWS_PRIVATE_BUCKET'),
        //             'Key' => $img,
        //         ]);

        //         $req = $s3Client->createPresignedRequest($cmd, '+20 minutes');
        //         $url = (string) $req->getUri();
        //     }

        //     return $url;
        // } catch (AWS\S3\Exception\S3Exception $e) {
        // }

        return '';
    }
}
