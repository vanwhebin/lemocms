<?php
/**
 * lemocms
 * ============================================================================
 * 版权所有 2018-2027 lemocms，并保留所有权利。
 * 网站地址: https://www.lemocms.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/8/2
 */
namespace app\admin\controller\sys;
use app\common\controller\Backend;
use app\common\model\Attach as AttachModel;
use think\facade\Config;
use think\facade\Env;
use think\facade\Request;
use lemo\helper\DataHelper;

class Uploads extends Backend{

    //上传验证规则
    protected $uploadValidate = [
        'file' => 'filesize:102400|fileExt:jpg,png,gif,jpeg,rar,zip,avi,mp4,rmvb,3gp,flv,mp3,txt,doc,xls,ppt,pdf,xls,docx,xlsx,doc'
    ];
    protected $imageValidate = [
        'image' => 'filesize:10240|fileExt:jpg,png,gif,jpeg,bmp,svg,webp'

    ];
    protected $videoValidate = [
        'video' => 'filesize:10240|avi,rmvb,3gp,flv,mp4'

    ];
    protected $voiceValidate = [
        'voice' => 'filesize:2048|mp3,wma,wav,amr'

    ];
    public function initialize()

    {
        $fileExt = getConfigByCode('upload_file_type');
        $filemax = getConfigByCode('upload_file_max')*1024;
        $this->imageValidate = ['image'=>
            'filesize'.$filemax.'|'.$fileExt,
        ];
        parent::initialize(); // TODO: Change the autogenerated stub
    }


    public function uploads($type='file'){

        //获取上传文件表单字段名
        $file =request()->file('file');
        $file_size = $file->getSize();
        $md5 = $file->md5();
        $sha1 = $file->sha1();;
        $file_mime = $file->getMime();
        $attach = AttachModel::where('md5',$md5)->find();
        if(!$attach){
            try {
                switch ($type=='file'){
                    case 'file':
                        $validate = $this->uploadValidate;
                        break;
                    case 'image':
                        $validate = $this->imageValidate;
                        break;
                    case 'video':
                        $validate = $this->videoValidate;
                        break;
                    case 'voice' :
                        $validate = $this->voiceValidate;
                        break;
                    default:
                        $validate = $this->uploadValidate;

                }
                validate($validate)
                    ->check(DataHelper::objToArray($file));
                $savename = \think\facade\Filesystem::disk('public')->putFile('uploads', $file);
                $path = '/storage/' . $savename;
            } catch (\think\exception\ValidateException $e) {
                $path = '';
                $error = $e->getMessage();
            }
            $file_ext =  strtolower(substr($savename, strrpos($savename, '.') + 1));
            $file_name = basename($savename);
            $width = $height = 0;
            if (in_array($file_mime, ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($file_ext, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
                $imgInfo = getimagesize($file->getPathname());
                ;
                if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                    $this->error(lang('Uploaded file is not a valid image'));
                }
                $width = isset($imgInfo[0]) ? $imgInfo[0] : $width;
                $height = isset($imgInfo[1]) ? $imgInfo[1] : $height;
            }
            if (!empty($path)) {
                $data = [
                    'admin_id'=>session('admin.id'),
                    'name'=>$file_name,
                    'path'=>$path,
                    'thumb'=>$path,
                    'url'=>$this->request->domain().$path,
                    'ext'=>$file_ext,
                    'size'=>$file_size/1024,
                    'width'=>$width,
                    'height'=>$height,
                    'md5'=>$md5,
                    'sha1'=>$sha1,
                    'mime'=>$file_mime,
                    'driver'=>'local',

                ];
                $attach = AttachModel::create($data);
                $result['code'] = 1;
                $result['id'] =$attach->id;
                $result["url"] = $path;
                $result['msg'] = lang('upload success');
                return json($result);
            } else {
                //上传失败获取错误信息
                $result['url'] = '';
                $result['msg'] = $error;
                $result['code'] = 0;
                return json($result);
            }

        }else{
            $result['code'] = 1;
            $result['id'] =$attach->id;
            //分辨是否截图上传，截图上传只能上传一个，非截图上传可以上传多个
            $result["url"] = $attach->path;
            $result['msg'] = lang('upload success');
            return json($result);
        }

    }

}