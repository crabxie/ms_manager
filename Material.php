<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/9/13
 * Time: 下午4:29
 */

namespace manager;

use libs\asyncme\RequestHelper;
use manager\model\AccountModel;
use manager\model\AssetsModel;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class Material extends PermissionBase
{

    /**
     * @name 首页
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function indexAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'material',
            'act'=>'material'
        ];
        $default_frame_url = urlGen($req,$path,$query,true);


        $data = [
            'default_frame_name'=>'素材管理',
            'default_frame_url'=>$default_frame_url,
        ];
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','material/index');
    }
    /**
     * @name 素材列表
     * @priv ask
     */
    public function materialAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);

        $where =[];
        $raw = false;
        $where =[];
        $raw = false;

        if ($req->request_method == 'POST') {
            $formget = $req->post_datas['formget'];
        } else {
            $keyword = urldecode($req->query_datas['keyword']);
            $formget['keyword'] = $keyword;
        }

        $formget['company_id'] = $req->company_id;

        if ($formget) {
            if ($formget['company_id'] && !$formget['keyword']) {
                $raw = false;
                $where['company_id']=$formget['company_id'];
            } else if  (isset($formget['group_id']) && $formget['keyword']) {
                $where[0] = "company_id = ?  and name = ?";
                $where[1] = [$formget['company_id'], '%'.$formget['keyword'].'%'];
                $raw = true;
            }
        }

        $assets_model = new model\AssetsModel($this->service);
        $total = $assets_model->assetsCount($where,$raw);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];

        $pageLink = urlGen($req,$path,[],true);
        $per_page = 20;
        $page = $this->page($pageLink,$total,$per_page);
        $lists = $assets_model->assetsLists($where,[['is_top','desc'],['is_hot','desc'],['is_favor','desc'],['ctime','desc']],$page->Current_page,$per_page,$raw);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'material',
        ];

        if ($lists) {

            foreach ($lists as $key=>$val) {
                $operater_url = array_merge($query,['act'=>'assest_edit','asset_id'=>$val['asset_id']]);
                $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                $operater_url = array_merge($query,['act'=>'assest_remove','asset_id'=>$val['asset_id']]);
                $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                if ($lists[$key]['is_hot_exipre']) {
                    if (time()-$lists[$key]['is_hot_exipre'] >0 ) {
                        $lists[$key]['is_hot']=0;
                        $assets_model->saveAssert(['asset_id'=>$val['asset_id']],['is_hot'=>0,'is_hot_exipre'=>0]);
                    }
                }

            }
            $operater_url = array_merge($query,['act'=>'assest_remove']);
            $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
        }

        $operater_url = array_merge($query,['act'=>'material_add']);
        $operaters_add_action =  urlGen($req,$path,$operater_url,true);

        $pagination = $page->show('Admin');

        //图片上传地址
        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'asset',
            'act'=>'upload',
            'tmp'=>'tmp',
        ];
        $asset_upload_url = urlGen($req,$path,$query,true);


        //$hook_model = new HookModel($this->service);
        //$hook_model->assert_max_sub_limit($req->company_id,$current_count);
        $data = [
            'total'=>$total,
            'lists' => $lists,
            'delete_action_url'=>$operaters_delete_action,
            'asset_upload_url'=>$asset_upload_url,
            'add_action_url'=>$operaters_add_action,
            'pagination' => $pagination,
            'formget'=>$formget,

        ];

        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','material/material');

    }

    /**
     * @name 添加资源
     * @priv ask
     */
    public function material_addAction(RequestHelper $req,array $preData)
    {

        try {
            if($req->request_method == 'POST') {
                $post = $req->post_datas;

                if ($post && $post['files']) {

                    $asset_model = new model\AssetsModel($this->service);
                    $account_model = new model\AccountModel($this->service);
                    //正常的编辑
                    $map = [];
                    $map['uid'] = $this->sessions['manager_uid'];
                    $check_account_where = [
                        'group_id'=>$req->company_id,
                        'id'=>$map['uid'],
                    ];

                    $exist = $account_model->getCompanyAccount($check_account_where);
                    if (!$exist) {
                        throw new \Exception('账号不存在');
                    }
                    $files = $post['files'];
                    $flag = true;
                    foreach ($files as $file) {
                        $origin_file = './data'.$file['filepath'];
                        if (file_exists($origin_file)) {

                            $origin_file_realpath = realpath($origin_file);
                            $map = [];
                            $map['thumb'] = '';


                            $file_info = pathinfo($origin_file);

                            $target_file = './data'.str_replace('/tmp/','/'.$req->company_id.'/',$file['filepath']);
                            $target_path = pathinfo($target_file);

                            if (in_array($file_info['extension'],['png','jpeg','jpg','gif'])) {
                                $thumb_file = str_replace($file_info['filename'],$file_info['filename']."_thumb",$target_file);
                                $thumb_file_realpath = str_replace($file_info['filename'],$file_info['filename']."_thumb",$origin_file_realpath);
                                $thumb_file_realpath = str_replace('/tmp/','/'.$req->company_id.'/',$thumb_file_realpath);
                                $imageick = new \Imagick($origin_file_realpath);
                                $imageick->thumbnailImage(200,200);
                                $map['thumb'] = str_replace('./data/','',$thumb_file);;
                                $img_data = $imageick->getImageBlob();
                                file_put_contents($thumb_file_realpath,$img_data);
                                unset($img_data);
                                $imageick->destroy();
                            }

                            $target_info = pathinfo($target_file);
                            if (!is_dir($target_info['dirname'].'/')) {
                                mkdir($target_info['dirname'].'/',0755,true);
                            }
                            @copy($origin_file,$target_file);
                            @unlink($origin_file);

                            $map['file'] = str_replace('./data/','',$target_file);

                            $map['asset_id'] = substr(md5($req->company_id.$file['id']),8,16);
                            $map['ctime'] = time();
                            $map['mtime'] = time();
                            $map['status'] = 1;

                            $map['company_id'] = $req->company_id;
                            $map['account_id'] = $this->sessions['manager_uid'];

                            $map['name'] = $file['name'];
                            $map['filesize'] = $file['size'];
                            $map['filetype'] = $file['type'];
                            if ($map['smeta']) {
                                $map['smeta'] = ng_mysql_json_safe_encode(json_decode($file['smeta'],true));
                            }
                            if (function_exists('hash_file')) {
                                $map['hash'] = hash_file('md5',$target_file);
                            } else {
                                $map['hash'] = 'not';
                            }

                            $flag1 = $asset_model->addAssert($map);
                            $flag = $flag && $flag1;

                        }

                    }


                    if (!$flag) {
                        throw new \Exception('保存错误');
                    } else {
                        $data = [
                            'info'=>'保存成功',
                        ];
                        $status = true;
                        $mess = '成功';
                    }

                }

            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $data = [
                'error'=>$error,
                'info'=>$error,
            ];
            $status = false;
            $mess = '失败';
        }

        if($req->request_method == 'POST') {
            //json返回
            return $this->render($status,$mess,$data);
        }

    }
    /**
     * @name 编辑资源
     * @priv ask
     */
    public function material_editAction()
    {

    }

    /**
     * @name 逻辑删除
     * @priv ask
     */
    public function work_removeAction()
    {

    }

    /**
     * @name 物理删除
     * @priv ask
     */
    public function work_deleteAction()
    {

    }
}