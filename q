[1mdiff --git a/routes/api.php b/routes/api.php[m
[1mindex d013d26..1354b0b 100644[m
[1m--- a/routes/api.php[m
[1m+++ b/routes/api.php[m
[36m@@ -510,27 +510,57 @@[m
 // use Image;[m
 Route::post('image/compression', function (Request $request) {[m
     if ($request->hasFile('avatar')) {[m
[32m+[m
[32m+[m[32m        $service = new AwsService();[m
[32m+[m
         $file = $request->file('avatar');[m
 [m
[31m-        $imageName = uniqid(date('YmdHis')) . '.' . $file->getClientOriginalExtension();[m
[31m-        $img = Image::make($file);[m
[32m+[m[32m        $imageName = time() . '.' . $file->getClientOriginalExtension();[m
[32m+[m
[32m+[m[32m        $relative_path =  'avatar/' . $imageName;[m
[32m+[m
[32m+[m[32m        $path = Storage::disk('s3')->put($relative_path, $request->file('avatar'));[m
[32m+[m
[32m+[m[32m        return response()->json([[m
[32m+[m[32m            'img' => $imageName,[m
[32m+[m[32m            'path' => $relative_path,[m
[32m+[m[32m            'url' => $path,[m
[32m+[m[32m        ]);[m
[32m+[m[32m        // $path = Storage::disk('s3')->put($relative_path, $request->file('avatar'));[m
[32m+[m
[32m+[m[32m        // $storagePath = Storage::disk('s3')->putFileAs('avatar', $request->file('avatar'), 'img_05' . time() . '.' . $request->file('avatar')->getClientOriginalExtension());[m
[32m+[m
[32m+[m[32m        // Required GD Library extension[m
[32m+[m[32m        // $img = Image::make($file);[m
[32m+[m
[32m+[m[32m        // $img->resize(400, 400);[m
[32m+[m
[32m+[m[32m        // $resource = $img->stream()->detach();[m
 [m
[31m-        $img->resize(400, 400);[m
 [m
[31m-        $resource = $img->stream()->detach();[m
[31m-        $filename = 'avatar/' . $imageName;[m
[32m+[m[32m        // $storagePath = Storage::disk('s3')->put([m
[32m+[m[32m        //     $filename,[m
[32m+[m[32m        //     $resource[m
[32m+[m[32m        // );[m
 [m
[31m-        $storagePath = Storage::disk('s3')->put([m
[31m-            $filename,[m
[31m-            $resource[m
[31m-        );[m
 [m
[32m+[m[32m        // return response()->json([[m
[32m+[m[32m        //     'msg' => 'Image compression via Intervention Image',[m
[32m+[m[32m        //     'status' => $storagePath,[m
[32m+[m[32m        //     'path'  => $file,[m
[32m+[m[32m        //     'filename' => $imageName,[m
[32m+[m[32m        // ]);[m
[32m+[m[32m        $x = $service->get_aws_object($path);[m
[32m+[m[32m        // $z = $service->get_aws_object($storagePath);[m
 [m
         return response()->json([[m
[31m-            'msg' => 'Image compression via Intervention Image',[m
[31m-            'status' => $storagePath,[m
[31m-            'path'  => $file,[m
[31m-            'filename' => $imageName,[m
[32m+[m[32m            'msg' => 'File upload via Storage',[m
[32m+[m[32m            // 'status' => $storagePath ? 'Success' : 'Failed',[m
[32m+[m[32m            'path' => $x,[m
[32m+[m[32m            // 'storage_path' => $z,[m
[32m+[m[32m            //'px' => $service->get_aws_object($path),[m
[32m+[m[32m            // 'url' => Storage::disk('s3')->get($file),[m
[32m+[m[32m            'avatars' => Storage::disk('s3')->files('avatar'),[m
         ]);[m
     }[m
 [m
