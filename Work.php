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
use manager\model\ConfigModel;
use manager\model\WorksAppModel;
use manager\model\WorksAppTemplateModel;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class Work extends PermissionBase
{

    /**
     * @name 首页
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function indexAction(RequestHelper $req, array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req, $preData);

        $path = [
            'mark' => 'manager',
            'bid' => $req->company_id,
            'pl_name' => 'manager',
        ];
        $query = [
            'mod' => 'work',
            'act' => 'work'
        ];
        $default_frame_url = urlGen($req, $path, $query, true);


        $data = [
            'default_frame_name' => '业务管理',
            'default_frame_url' => $default_frame_url,
        ];
        $data = array_merge($nav_data, $data);

        return $this->render($status, $mess, $data, 'template', 'work/index');
    }

    /**
     * @name 业务列表
     * @priv ask
     */
    public function workAction(RequestHelper $req, array $preData)
    {
        try{
            $nav_data = $this->nav_default($req,$preData);
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
                } else if  (isset($formget['company_id']) && $formget['keyword']) {
                    $where[0] = "company_id = ?  and name = ? ";
                    $where[1] = [$formget['company_id'], '%'.$formget['keyword'].'%'];
                    $raw = true;
                }
            }

            $works_model = new model\WorkModel($this->service);
            $total = $works_model->worksCount($where,$raw);
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];

            $pageLink = urlGen($req,$path,[],true);
            $per_page = 20;
            $page = $this->page($pageLink,$total,$per_page);
            $lists = $works_model->worksLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
            ];
            $worksapp_model = new model\WorksAppModel($this->service);
            if ($lists) {

                foreach ($lists as $key=>$val) {

                    $lists[$key]['type_id_name'] = $this->work_cates($val['type_id']+1);

                    if($val['config']) {
                        $config = ng_mysql_json_safe_decode($val['config']);
                        if (isset($config['thumb'])) {
                            $lists[$key]['thumb'] =$config['thumb'];
                        }
                    }
                    $operater_url = array_merge($query,['act'=>'work_edit','work_id'=>$val['work_id']]);
                    $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_delete','work_id'=>$val['work_id']]);
                    $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_app','work_id'=>$val['work_id']]);
                    $lists[$key]['app_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_app_add','work_id'=>$val['work_id']]);
                    $lists[$key]['add_app_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_admin_add','work_id'=>$val['work_id']]);
                    $lists[$key]['add_admin_url'] = urlGen($req,$path,$operater_url,true);

                    if($val['account_id']==$this->sessions['manager_uid']) {
                        $lists[$key]['is_self'] = true;
                    }else {
                        $lists[$key]['is_self'] = false;
                    }
                    $lists[$key]['is_admin'] = true;


                    $worksapp_where = ['work_id'=>$val['work_id'],'company_id'=>$req->company_id];
                    $lists[$key]['app_count'] = $worksapp_model->worksAppCount($worksapp_where);

                    $admin_count_where = ['company_id'=>$req->company_id,'work_id'=>$val['work_id']];
                    $lists[$key]['admin_count'] = $works_model->worksAdminCount($admin_count_where);


                }
                $operater_url = array_merge($query,['act'=>'work_delete']);
                $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
            }

            $operater_url = array_merge($query,['act'=>'work_add']);
            $operaters_add_action =  urlGen($req,$path,$operater_url,true);

            $pagination = $page->show('Admin');

            $data = [
                'total'=>$total,
                'lists' => $lists,
                'add_action_url'=>$operaters_add_action,
                'delete_action_url'=>$operaters_delete_action,
                'pagination' => $pagination,
                'formget'=>$formget,
            ];


        } catch(\Exception $e) {
            $error = $e->getMessage();
        }
        if ($error) {
            $status = false;
            $mess = $error;
            $data = [];
        } else {
            $status = true;
            $mess = '成功';
        }
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','work/work');
    }

    /**
     * @name 删除业务
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function work_deleteAction(RequestHelper $req, array $preData)
    {
        if ($req->request_method=='POST') {
            $remove_uids = $req->post_datas['ids'];
        } else {
            $request_uid = $req->query_datas['work_id'];
            $remove_uids = [$request_uid];
        }

        $flag = true;
        if (!empty($remove_uids)) {
            $work_model = new model\WorkModel($this->service);

            foreach ($remove_uids as $remove_id) {
                $where = ['work_id'=>$remove_id];
                $res = $work_model->deleteWorks($where);
                $work_model->deleteWorksAdmin($where);
                $flag = $flag && $res;
            }

        }

        if ($flag) {
            $status = true;
            $mess = '成功';
            $data = [
                'info'=>$mess,
            ];
        } else {
            $status = false;
            $mess = '失败，该账号不允许删除';
            $data = [
                'info'=>$mess,
            ];
        }

        return $this->render($status,$mess,$data);
    }

    /**
     * @name 添加业务
     * @priv ask
     */
    public function work_addAction(RequestHelper $req, array $preData)
    {
        try {
            //返回地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work'
            ];
            $cate_index_url=  urlGen($req,$path,$query,true);

            //图片上传地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'asset',
                'act'=>'upload',
            ];
            $asset_upload_url = urlGen($req,$path,$query,true);

            $cates = $this->work_cates();

            $status = true;
            $mess = '成功';
            $data = [
                'cate_name'=>'业务管理',
                'op'=>'add',
                'cate_index_url'=>$cate_index_url,
                'cates'=>$cates,
                'asset_upload_url'=>$asset_upload_url,
            ];
            $work_model = new model\WorkModel($this->service);
            $current_count = $work_model->worksCount(['company_id'=>$req->company_id]);

            $hook_model = new model\HookModel($this->service);
            $hook_model->assert_max_work_limit($req->company_id,$current_count);


            if($req->request_method == 'POST') {
                $post = $req->post_datas['post'];
                if ($post) {
                    //正常的编辑
                    $map = [];
                    if ($post['name'] ) {
                        $map['name'] = $post['name'];
                    } else {
                        throw new \Exception('账号不对。');
                    }
                    $check_account_where = [
                        'name'=>$map['name'],
                    ];
                    $exist = $work_model->worksCount($check_account_where);
                    if ($exist) {
                        throw new \Exception('业务已经存在');
                    }

                    $map['type_id'] = $post['type_id'];
                    $map['company_id'] = $req->company_id;

                    if ($post['desc']) {
                        $map['config']['desc'] = ['desc'=>htmlspecialchars($post['desc'])];
                    }

                    if ($post['thumb']) {
                        $map['config']['thumb'] = $post['thumb'];
                    }
                    if (!empty($map['config'])) {
                        $map['config'] = ng_mysql_json_safe_encode($map['config']);
                    }

                    $map['account_id'] = $this->sessions['manager_uid'];
                    $map['status'] = $post['status'];
                    $map['ctime'] = time();
                    $map['mtime'] = time();


                    $flag = $work_model->addWorks($map);
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
        }catch (\Exception $e) {
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
        } else {

            return $this->render($status,$mess,$data,'template','work/work_edit');
        }
    }
    /**
     * @name 编辑业务
     * @priv ask
     */
    public function work_editAction(RequestHelper $req, array $preData)
    {
        $request_id = $req->query_datas['work_id'];
        try {
            $works_model = new model\WorkModel($this->service);
            if ($request_id) {
                //返回地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'work',
                    'act'=>'work'
                ];
                $cate_index_url=  urlGen($req,$path,$query,true);

                //图片上传地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'asset',
                    'act'=>'upload',
                ];
                $asset_upload_url = urlGen($req,$path,$query,true);


                $work_info = $works_model->worksInfo(['work_id'=>$request_id]);
                if (!$work_info) {
                    throw new \Exception('业务不存在');
                }

                if ($work_info['config']) {
                    $work_info['config'] = ng_mysql_json_safe_decode($work_info['config']);
                    if($work_info['config']['thumb']){
                        $work_info['thumb'] = $work_info['config']['thumb'];
                    }
                    if ($work_info['config']['desc']) {
                        $work_info['desc'] = htmlspecialchars_decode($work_info['config']['desc']['desc']);
                    }

                }
                $cates = $this->work_cates();

                $data = [
                    'cate_index_url'=>$cate_index_url,
                    'asset_upload_url'=>$asset_upload_url,
                    'cate_name'=>'业务管理',
                    'info'=>$work_info,
                    'cates'=>$cates,
                ];
                $status = true;
                $mess = '成功';

                if($req->request_method == 'POST') {
                    $post = $req->post_datas['post'];

                    if ($post) {

                        $map = [];
                        if ($post['name'] ) {
                            $map['name'] = $post['name'];
                        } else {
                            throw new \Exception('账号不对。');
                        }

                        $map['type_id'] = $post['type_id'];

                        if ($post['desc']) {
                            $map['config']['desc'] = ['desc'=>htmlspecialchars($post['desc'])];
                        }

                        if ($post['thumb']) {
                            $map['config']['thumb'] = $post['thumb'];
                        }
                        if (!empty($map['config'])) {
                            $map['config'] = ng_mysql_json_safe_encode($map['config']);
                        }
                        $map['status'] = $post['status'];
                        $map['mtime'] = time();

                        $save_where = [
                            'company_id'=>$req->company_id,
                            'work_id'=> $post['work_id'],
                        ];

                        $flag = $works_model->saveWorks($save_where,$map);
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
        } else {
            return $this->render($status,$mess,$data,'template','work/work_edit');
        }
    }

    /**
     * @name 添加管理员
     * @priv ask
     */
    public function work_admin_addAction(RequestHelper $req, array $preData)
    {
        try{
            $work_id = $req->query_datas['work_id'];
            if(!$work_id) {
                throw new \Exception('业务id不正确');
            }
            $account_model = new model\AccountModel($this->service);
            $self_company_uid = $this->sessions['manager_uid'];
            if(!$self_company_uid) {
                throw new \Exception('运营者不正确');
            }
            $account_info = $account_model->getCompanyAccount(['id'=>$self_company_uid]);
            if ($account_info['group_type']>0) {
                throw new \Exception('运营者不是主账号');
            }

            $work_model = new model\WorkModel($this->service);
            $work_where = ['company_id'=>$req->company_id,'work_id'=>$work_id];
            $work_admin = $work_model->worksAdminLists($work_where);
            $current_admin = [];
            if ($work_admin) {
                foreach ($work_admin as $key=>$val) {
                    $current_admin[$val['account_id']] = $val['account_nickname'];
                }
            }

            if ($req->request_method=='POST') {
                $post = $req->post_datas['post'];
                $post_work_id = $post['work_id'];
                $account_ids = $post['account_id'];
                $account_nickname = $post['account_nickname'];
                if ($account_ids) {
                    $remove_ids = $account_ids;

                    foreach ($account_ids as $key=>$val) {

                        if (!empty($current_admin) && isset($current_admin[$val])) {
                            //存在
                            unset($remove_ids[$key]);
                            continue;
                        } else {
                            //添加
                            unset($remove_ids[$key]);
                            $admin_map = [
                                'company_id'=>$req->company_id,
                                'work_id'=>$post_work_id,
                                'account_id'=>$val,
                                'account_nickname'=>$account_nickname[$key],
                                'status'=>1,
                                'expire_time'=>0,
                                'ctime'=>time(),
                                'mtime'=>time(),
                            ];
                            $work_model->addWorksAdmin($admin_map);
                        }
                    }
                    if ($remove_ids) {
                        foreach($remove_ids as $remove_id) {
                            $remove_where = [
                                'company_id'=>$req->company_id,
                                'work_id'=>$post_work_id,
                                'account_id'=>$remove_id,
                            ];
                            $work_model->deleteWorksAdmin($remove_where);
                        }
                    }
                } else {
                    $work_model->deleteWorksAdmin($work_where);
                }
                $status = true;
                $mess = '成功';
                $data = [
                    'info'=>$mess,
                    'status'=>$status,
                ];
            } else {
                $account_where = [
                    'group_id'=>$req->company_id,
                    'group_type'=>1,
                ];
                $lists = $account_model->companyLists($account_where);
                // 获取已经是管理员的

                if ($lists) {
                    foreach($lists as $key=>$val) {
                        if (isset($current_admin[$val['id']])) {
                            $lists[$key]['checked'] = true;
                        } else {
                            $lists[$key]['checked'] = false;
                        }
                    }
                }
                //返回地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'work',
                    'act'=>'work'
                ];
                $cate_index_url=  urlGen($req,$path,$query,true);

                $status = true;
                $mess = '成功';
                $data = [
                    'lists'=>$lists,
                    'work_id'=>$work_id,
                    'cate_index_url'=>$cate_index_url,
                ];
            }



        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        if($error) {
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
        } else {

            return $this->render($status,$mess,$data,'template','work/work_admin_edit');
        }

    }

    /**
     * @name 业务应用列表
     * @priv ask
     */
    public function work_appAction(RequestHelper $req, array $preData)
    {
        try{
            $nav_data = $this->nav_default($req,$preData);

            $where =[];
            $raw = false;

            $request_work_id = $req->query_datas['work_id'];

            if ($req->request_method == 'POST') {
                $formget = $req->post_datas['formget'];
            } else {
                $keyword = urldecode($req->query_datas['keyword']);
                $formget['keyword'] = $keyword;
            }
            $formget['company_id'] = $req->company_id;
            $formget['work_id'] = $request_work_id;

            if ($formget) {
                if ($formget['company_id'] && !$formget['keyword']) {
                    $raw = false;
                    $where['company_id']=$formget['company_id'];
                    $where['work_id']=$formget['work_id'];

                } else if  (isset($formget['company_id']) && $formget['keyword']) {
                    $where[0] = "company_id = ?  and name like ? and work_id = ? ";
                    $where[1] = [$formget['company_id'], '%'.$formget['keyword'].'%',$formget['work_id']];
                    $raw = true;
                }
            }

            $worksApp_model = new model\WorksAppModel($this->service);
            $total = $worksApp_model->worksAppCount($where,$raw);
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];

            $pageLink = urlGen($req,$path,[],true);
            $per_page = 20;
            $page = $this->page($pageLink,$total,$per_page);
            $lists = $worksApp_model->worksAppLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
            ];

            if ($lists) {
                foreach ($lists as $key=>$val) {
                    $lists[$key]['type_id_name'] = $this->works_app_cates($val['type_id']+1);
                    if($val['config']) {
                        $config = ng_mysql_json_safe_decode($val['config']);
                        if (isset($config['thumb'])) {
                            $lists[$key]['thumb'] =$config['thumb'];
                        }
                    }
                    $operater_url = array_merge($query,['act'=>'work_app_edit','app_sid'=>$val['app_sid'],'work_id'=>$val['work_id']]);
                    $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_app_delete','app_sid'=>$val['app_sid'],'work_id'=>$val['work_id']]);
                    $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                    $operater_url = array_merge($query,['act'=>'work_app_copy','app_sid'=>$val['app_sid'],'work_id'=>$val['work_id']]);
                    $lists[$key]['copy_url'] = urlGen($req,$path,$operater_url,true);



                }
                $operater_url = array_merge($query,['act'=>'work_app_delete','work_id'=>$request_work_id]);
                $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
            }

            $operater_url = array_merge($query,['act'=>'work_app_add','work_id'=>$request_work_id]);
            $operaters_add_action =  urlGen($req,$path,$operater_url,true);

            $pagination = $page->show('Admin');

            $data = [
                'total'=>$total,
                'lists' => $lists,
                'add_action_url'=>$operaters_add_action,
                'delete_action_url'=>$operaters_delete_action,
                'pagination' => $pagination,
                'formget'=>$formget,
                'work_id'=>$request_work_id,
            ];


        } catch(\Exception $e) {
            $error = $e->getMessage();
        }
        if ($error) {
            $status = false;
            $mess = $error;
            $data = [];
        } else {
            $status = true;
            $mess = '成功';
        }
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','work/worksapp');
    }

    /**
     * @name 删除应用
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function work_app_deleteAction(RequestHelper $req, array $preData)
    {
        $request_work_id = $req->query_datas['work_id'];
        if ($req->request_method=='POST') {
            $remove_uids = $req->post_datas['ids'];
        } else {
            $request_uid = $req->query_datas['app_sid'];
            $remove_uids = [$request_uid];
        }

        $flag = true;
        if (!empty($remove_uids)) {
            $work_model = new model\WorksAppModel($this->service);

            foreach ($remove_uids as $remove_id) {
                $where = ['app_sid'=>$remove_id,'work_id'=>$request_work_id];
                $res = $work_model->deleteWorksApp($where);
                $flag = $flag && $res;
            }

        }

        if ($flag) {
            $status = true;
            $mess = '成功';
            $data = [
                'info'=>$mess,
                'status'=>$status,
            ];
        } else {
            $status = false;
            $mess = '失败，该账号不允许删除';
            $data = [
                'info'=>$mess,
                'status'=>$status,
            ];
        }

        return $this->render($status,$mess,$data);
    }

    /**
     * @name 配置对话框
     * @param RequestHelper $req
     * @param array $preData
     */
    public function work_app_config_dialogAction(RequestHelper $req, array $preData)
    {
        $request_type_id = $req->query_datas['type_id'];
        $request_app_sid = $req->query_datas['app_sid'];
        $where = [
            'status'=>1,
        ];
        if ($request_type_id) {
            $where['type_id']=$request_type_id;
        }
        $raw = false;
        $config_model = new model\ConfigModel($this->service);

        //需要根据不同的小程序类型获取不同的配置
        $config_template_name = 'manager_pay_minapp_setting';
        $config_info = $config_model->getConfigInfo(['name'=>$config_template_name]);
        if (!$config_info) {
            throw new \Exception('请配置"'.$config_template_name.'"');
        }
        $config = ng_mysql_json_safe_decode($config_info['config']);
        $config_type = [];
        $config_desc = ng_mysql_json_safe_decode($config_info['config_desc']);

        if($request_app_sid>0) {
            $workApp_model = new model\WorksAppModel($this->service);
            $workApp_info = $workApp_model->worksAppInfo(['app_sid'=>$request_app_sid]);
            if ($workApp_info && $workApp_info['config']) {
                $current_config = ng_mysql_json_safe_decode($workApp_info['config']);
                if ($current_config) {
                    $keys = array_merge(array_keys($config),array_keys($current_config));
                    foreach ($keys as $key) {

                        $config[$key] = $current_config[$key];
                    }
                }
            }
        }
        foreach ($config as $key=>$val) {
            $config_type[$key] = "text";
            if(preg_match("/(.+):\/\//is",$key,$rs)) {
                $old_key=$key;

                $key = str_replace($rs[1]."://","",$key);
                $config[$key] = $val;
                $config_type[$key] = $rs[1];

                $config_desc[$key]=$config_desc[$old_key];
                unset($config[$old_key]);
                unset($config_type[$old_key]);
                unset($config_desc[$old_key]);
            }
        }

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


        $status = true;
        $mess = '成功';
        $data = [
            'configs'=>$config,
            'config_desc'=>$config_desc,
            'config_type'=>$config_type,
            'asset_upload_url'=>$asset_upload_url,
        ];
        return $this->render($status,$mess,$data,'template','work/config_dialog');


    }
    /**
     * @name 模版对话框
     * @param RequestHelper $req
     * @param array $preData
     */
    public function work_app_template_dialogAction(RequestHelper $req, array $preData)
    {
        $request_type_id = $req->query_datas['type_id'];
        $where = [
            'status'=>1,
        ];
        if ($request_type_id) {
            $where['type_id']=$request_type_id;
        }
        $raw = false;

        $work_app_template_model = new model\WorksAppTemplateModel($this->service);
        $total = $work_app_template_model->worksAppTemplateCount($where,$raw);
        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];

        $pageLink = urlGen($req,$path,[],true);
        $per_page = 20;
        $page = $this->page($pageLink,$total,$per_page);
        $work_app_template_lists = $work_app_template_model->worksAppTemplateLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

        if ($work_app_template_lists) {
            foreach($work_app_template_lists  as $key => $template) {
                if($template['preview']) {
                    $template['preview'] = ng_mysql_json_safe_decode($template['preview']);
                }
                if($template['config']) {
                    $template['config'] = ng_mysql_json_safe_decode($template['config']);
                }
                $work_app_template_lists[$key] = $template;
            }
        }
        $pagination = $page->show('Admin');

        $status = true;
        $mess = '成功';
        $data = [
            'info'=>$mess,
            'lists'=>$work_app_template_lists,
            'total'=>$total,
            'pagination' => $pagination,
        ];
        return $this->render($status,$mess,$data,'template','work_app_template/dialog');
    }
    /**
     * @name 添加应用
     * @priv ask
     */
    public function work_app_addAction(RequestHelper $req, array $preData)
    {
        try {
            $request_work_id = $req->query_datas['work_id'];
            //返回地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app',
                'work_id'=>$request_work_id,
            ];

            $cate_index_url=  urlGen($req,$path,$query,true);

            //对话框获取模版
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app_template_dialog',
            ];
            $template_index_url=  urlGen($req,$path,$query,true);

            //对话框获取配置
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app_config_dialog',
            ];
            $config_url=  urlGen($req,$path,$query,true);



            //图片上传地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'asset',
                'act'=>'upload',
            ];
            $asset_upload_url = urlGen($req,$path,$query,true);

            $cates = $this->works_app_cates();

            $status = true;
            $mess = '成功';
            $data = [
                'cate_name'=>'应用管理',
                'op'=>'add',
                'cate_index_url'=>$cate_index_url,
                'cates'=>$cates,
                'asset_upload_url'=>$asset_upload_url,
                'template_index_url'=>$template_index_url,
                'config_url'=>$config_url,
                'work_id'=>$request_work_id,
            ];
            $work_model = new model\WorksAppModel($this->service);
            $current_count = $work_model->worksAppCount(['company_id'=>$req->company_id,'work_id'=>$request_work_id]);

            $hook_model = new model\HookModel($this->service);
            $hook_model->assert_max_app_limit($req->company_id,$current_count);

            if($req->request_method == 'POST') {
                $post = $req->post_datas['post'];
                if ($post) {
                    //正常的编辑
                    $map = [];
                    if ($post['name'] ) {
                        $map['name'] = $post['name'];
                    } else {
                        throw new \Exception('账号不对。');
                    }
                    $check_account_where = [
                        'name'=>$map['name'],
                        'company_id'=>$req->company_id,
                        'work_id'=>$request_work_id,
                    ];
                    $exist = $work_model->worksAppCount($check_account_where);
                    if ($exist) {
                        throw new \Exception('应用已经存在');
                    }

                    if(!$post['template_sid']) {
                        throw new \Exception('请选择模版');
                    }
                    $works_app_template_model = new model\WorksAppTemplateModel($this->service);
                    $works_app_template_info = $works_app_template_model->worksAppTemplateInfo(['template_sid'=>$post['template_sid'],'status'=>1]);
                    if (!$works_app_template_info || !$works_app_template_info['config']) {
                        throw new \Exception('模版不存在');
                    }
                    $map['config'] = $works_app_template_info['config'];
                    $map['config']['template_sid'] = $post['template_sid'];
                    if ($post['template_name']) {
                        $map['config']['template_name'] = $post['template_name'];
                    }

                    if (!empty($map['config'])) {
                        $map['config'] = ng_mysql_json_safe_encode($map['config']);
                    }

                    $map['type_id'] = $post['type_id'];
                    $map['company_id'] = $req->company_id;
                    $map['work_id'] = $request_work_id;


                    if ($post['thumb']) {
                        $map['icon'] = $post['thumb'];
                    }

                    if(!$post['config_value']) {
                        throw new \Exception('请填写配置');
                    }
                    $config_value = preg_replace_callback(
                        "/post_c\[(.+?)\]/is",
                        function ($matches) {
                            return strtolower($matches[1]);
                        },
                        $post['config_value']
                        );
                    $config_value = json_decode($config_value,true);
                    $map['project_config'] = [];
                    if (is_array($config_value)) {
                        foreach($config_value as $val) {
                            $map['project_config'][$val['name']] = $val['val'];
                        }
                    }
                    if (isset($map['project_config']['cert_file'])) {
                        $cert_file_info = pathinfo($map['project_config']['cert_file']);
                        if (!in_array(strtolower($cert_file_info['extension']),['p12','pem'])) {
                            unset($map['project_config']['cert_file']);
                        } else {
                            if(preg_match('/^\/tmp\//is',$map['project_config']['cert_file'])) {
                                $origin_file = './data'.$map['project_config']['cert_file'];
                                if (!file_exists($origin_file)) {
                                    throw new \Exception('证书文件不存在');
                                }

                                //移动
                                $config_model = new model\ConfigModel($this->service);
                                $cert_global = 'cert_global';
                                $cert_global_config = $config_model->getConfig($cert_global);
                                if (!$cert_global_config || !$cert_global_config['cert_path']) {
                                    throw new \Exception('请设置证书安装路径');
                                }
                                $target_file = str_replace('/tmp/','/'.$req->company_id.'/',$map['project_config']['cert_file']);

                                $target_file = '..'.$cert_global_config['cert_path'].''.$target_file;
                                $target_info = pathinfo($target_file);

                                if (!is_dir($target_info['dirname'].'/')) {
                                    mkdir($target_info['dirname'].'/',0755,true);
                                }

                                @copy($origin_file,$target_file);
                                unlink($origin_file);
                                $map['project_config']['cert_file'] = $target_file;

                            } else {
                                //编辑时候有用，不处理
                            }
                        }

                    }
                    if (!empty($map['project_config'])) {
                        $map['project_config'] = ng_mysql_json_safe_encode($map['project_config']);
                    } else {
                        throw new \Exception('配置不正确');
                    }

                    $map['account_id'] = $this->sessions['manager_uid'];
                    $map['account_nickname'] = $this->sessions['manager_name'];
                    $map['status'] = $post['status'];
                    $map['ctime'] = time();
                    $map['mtime'] = time();


                    $flag = $work_model->addWorksApp($map);
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
        }catch (\Exception $e) {
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
        } else {
            return $this->render($status,$mess,$data,'template','work/worksapp_edit');
        }
    }

    /**
     * @name 编辑应用
     * @priv ask
     */
    public function work_app_editAction(RequestHelper $req, array $preData)
    {
        try {
            $request_work_id = $req->query_datas['work_id'];
            $request_app_sid = $req->query_datas['app_sid'];
            //返回地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app',
                'work_id'=>$request_work_id,
            ];

            $cate_index_url=  urlGen($req,$path,$query,true);

            //对话框获取模版
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app_template_dialog',
            ];
            $template_index_url=  urlGen($req,$path,$query,true);

            //对话框获取配置
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'work',
                'act'=>'work_app_config_dialog',
            ];
            $config_url=  urlGen($req,$path,$query,true);



            //图片上传地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'asset',
                'act'=>'upload',
            ];
            $asset_upload_url = urlGen($req,$path,$query,true);

            $cates = $this->works_app_cates();

            $work_model = new model\WorksAppModel($this->service);
            $where = [
                'company_id'=>$req->company_id,
                'work_id'=>$request_work_id,
                'app_sid'=>$request_app_sid,
            ];
            $worksApp_info = $work_model->worksAppInfo($where);
            if (!$worksApp_info) {
                throw new \Exception('应用不存在');
            }
            $worksApp_info['template_name'] = $worksApp_info['config']['template_name'];
            $worksApp_info['template_sid'] = $worksApp_info['config']['template_sid'];

            if ($worksApp_info['project_config']) {
                $worksApp_info['project_config_count'] = count($worksApp_info['project_config']);
                $project_config = [];
                foreach($worksApp_info['project_config'] as $key=>$val) {
                    $project_config[] = ['name'=>'post_c['.$key.']','val'=>$val];
                }
                $worksApp_info['project_config'] = json_encode($project_config);

            }

            $status = true;
            $mess = '成功';
            $data = [
                'cate_name'=>'应用管理',
                'cate_index_url'=>$cate_index_url,
                'cates'=>$cates,
                'info'=>$worksApp_info,
                'asset_upload_url'=>$asset_upload_url,
                'template_index_url'=>$template_index_url,
                'config_url'=>$config_url,
                'work_id'=>$request_work_id,
                'app_sid'=>$request_app_sid,
            ];


            if($req->request_method == 'POST') {
                $post = $req->post_datas['post'];
                if ($post) {
                    //正常的编辑
                    $map = [];
                    if ($post['name'] ) {
                        $map['name'] = $post['name'];
                    } else {
                        throw new \Exception('账号不对。');
                    }
                    $check_account_where = [
                        'name'=>$map['name'],
                        'company_id'=>$req->company_id,
                        'work_id'=>$request_work_id,
                    ];
                    $worksApp_info = $work_model->worksAppInfo($check_account_where);
                    if ($worksApp_info && $worksApp_info['app_sid']!=$request_app_sid) {
                        throw new \Exception('应用已经存在');
                    }

                    if(!$post['template_sid']) {
                        throw new \Exception('请选择模版');
                    }
                    $works_app_template_model = new model\WorksAppTemplateModel($this->service);
                    $works_app_template_info = $works_app_template_model->worksAppTemplateInfo(['template_sid'=>$post['template_sid'],'status'=>1]);
                    if (!$works_app_template_info || !$works_app_template_info['config']) {
                        throw new \Exception('模版不存在');
                    }
                    $map['config'] = $works_app_template_info['config'];

                    if ($post['template_name']) {
                        $map['config']['template_name'] = $post['template_name'];
                    }


                    if (!empty($map['config'])) {
                        $map['config'] = ng_mysql_json_safe_encode($map['config']);
                    }


                    $map['type_id'] = $post['type_id'];
                    $map['company_id'] = $req->company_id;
                    $map['work_id'] = $request_work_id;


                    if ($post['thumb']) {
                        $map['icon'] = $post['thumb'];
                    }


                    if(!$post['config_value']) {
                        throw new \Exception('请填写配置');
                    }
                    $config_value = preg_replace_callback(
                        "/post_c\[(.+?)\]/is",
                        function ($matches) {
                            return strtolower($matches[1]);
                        },
                        $post['config_value']
                    );
                    $config_value = json_decode($config_value,true);
                    $map['project_config'] = [];
                    if (is_array($config_value)) {
                        foreach($config_value as $val) {
                            $map['project_config'][$val['name']] = $val['val'];
                        }
                    }
                    if (isset($map['project_config']['cert_file'])) {
                        $cert_file_info = pathinfo($map['project_config']['cert_file']);
                        if (!in_array(strtolower($cert_file_info['extension']),['p12','pem'])) {
                            unset($map['project_config']['cert_file']);
                        } else {
                            if(preg_match('/^\/tmp\//is',$map['project_config']['cert_file'])) {
                                $origin_file = './data'.$map['project_config']['cert_file'];
                                if (!file_exists($origin_file)) {
                                    throw new \Exception('证书文件不存在');
                                }

                                //移动
                                $config_model = new model\ConfigModel($this->service);
                                $cert_global = 'cert_global';
                                $cert_global_config = $config_model->getConfig($cert_global);
                                if (!$cert_global_config || !$cert_global_config['cert_path']) {
                                    throw new \Exception('请设置证书安装路径');
                                }
                                $target_file = str_replace('/tmp/','/'.$req->company_id.'/',$map['project_config']['cert_file']);

                                $target_file = '..'.$cert_global_config['cert_path'].''.$target_file;
                                $target_info = pathinfo($target_file);

                                if (!is_dir($target_info['dirname'].'/')) {
                                    mkdir($target_info['dirname'].'/',0755,true);
                                }

                                @copy($origin_file,$target_file);
                                unlink($origin_file);
                                $map['project_config']['cert_file'] = $target_file;

                            } else {
                                //编辑时候有用，不处理
                            }
                        }

                    }
                    if (!empty($map['project_config'])) {
                        $map['project_config'] = ng_mysql_json_safe_encode($map['project_config']);
                    } else {
                        throw new \Exception('配置不正确');
                    }

                    $map['account_id'] = $this->sessions['manager_uid'];
                    $map['account_nickname'] = $this->sessions['manager_name'];
                    $map['status'] = $post['status'];
                    $map['mtime'] = time();


                    $flag = $work_model->saveWorksApp($where,$map);
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
        }catch (\Exception $e) {
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
        } else {
            return $this->render($status,$mess,$data,'template','work/worksapp_edit');
        }
    }

    /**
     * @name 复制应用
     * @priv ask
     */
    public function work_app_copyAction(RequestHelper $req, array $preData)
    {
        try{
            $request_work_id = $req->query_datas['work_id'];
            $request_app_sid = $req->query_datas['app_sid'];
            if(!$request_work_id || !$request_app_sid) {
                throw new \Exception('应用id错误');
            }
            $work_model = new model\WorksAppModel($this->service);
            //检查是否超过限制了
            $current_count = $work_model->worksAppCount(['company_id'=>$req->company_id,'work_id'=>$request_work_id]);
            $hook_model = new model\HookModel($this->service);
            $hook_model->assert_max_app_limit($req->company_id,$current_count);

            $where = [
                'company_id'=>$req->company_id,
                'work_id'=>$request_work_id,
                'app_sid'=>$request_app_sid,
            ];
            $worksApp_info = $work_model->worksAppInfo($where);
            if (!$worksApp_info) {
                throw new \Exception('应用不存在');
            }
            $map = $worksApp_info;
            unset($map['id']);unset($map['app_sid']);
            $map['ctime'] = $map['mtime'] = time();
            // 重新名
            while(true) {
                //无限循环生成应用名称，确保应用名称不重复
                $suffix = ng_copy_name_gen();
                $new_name = $map['name'].'_'.$suffix;
                $check_name_where = [
                    'company_id'=>$req->company_id,
                    'work_id'=>$request_work_id,
                    'name'=>$new_name,
                ];
                $check_exist = $work_model->worksAppCount($check_name_where);
                if (!$check_exist)break;
            }
            $map['name'] = $new_name;
            if (!empty($map['config'])) {
                $map['config'] = ng_mysql_json_safe_encode($map['config']);
            }
            if (!empty($map['project_config'])) {
                $map['project_config'] = ng_mysql_json_safe_encode($map['project_config']);
            }

            $flag = $work_model->addWorksApp($map);

            if (!$flag) {
                throw new \Exception('复制失败');
            }
            $status = true;
            $mess = '复制成功';
            $data = [
                'status'=>$status,
                'info'=>$mess,
            ];

        }catch (\Exception $e) {
            $error = $e->getMessage();
            $status = false;
            $mess = '失败';
            $data = [
                'error'=>$error,
                'info'=>$error,
                'status'=>$status,
            ];

        }
        if($req->request_method == 'GET') {
            //json返回
            return $this->render($status, $mess, $data);
        }
    }
    /**
     * @name 分类设置
     * @priv allow
     */
    protected function work_cates($cate='')
    {
        if(!is_numeric($cate)) $cate='**';
        $cates = [
            0=>'默认分类',
            1=>'小程序',
            2=>'网站',
        ];

        if('**'==$cate) {
            return $cates;
        } else {
            return $cates[$cate-1];
        }
    }

    /**
     * @name 分类设置
     * @priv allow
     */
    protected function works_app_cates($cate='')
    {
        if(!is_numeric($cate)) $cate='**';
        $cates = [
            0=>'默认分类',
            1=>'新零售商城',
            2=>'企业展示',
            3=>'工具类',
            4=>'生活服务',
            5=>'O2O',
            6=>'服务预定',
            6=>'互动功能',
            7=>'文章资讯',
        ];

        if('**'==$cate) {
            return $cates;
        } else {
            return $cates[$cate-1];
        }
    }
}