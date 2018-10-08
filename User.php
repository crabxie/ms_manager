<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/8/4
 * Time: 下午2:32
 */

namespace manager;

use libs\asyncme\RequestHelper;
use manager\model\HookModel;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class User extends PermissionBase
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
            'mod'=>'user',
            'act'=>'company'
        ];
        $default_frame_url = urlGen($req,$path,$query,true);

        //ng_func_privilege_check($req->company_id,$this->sessions['admin_uid'],'index');

        $data = [
            'default_frame_name'=>'运营者',
            'default_frame_url'=>$default_frame_url,
        ];
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','user/index');
    }


    //运营者


    /**
     * @name 运营者子账号
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function companyAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);

        $where =[];
        $raw = false;

        if ($req->request_method == 'POST') {
            $formget = $req->post_datas['formget'];
        } else {
            $keyword = urldecode($req->query_datas['keyword']);
            $formget['keyword'] = $keyword;
        }

        $formget['group_id'] = $req->company_id;

        if ($formget) {
            if ($formget['group_id'] && !$formget['keyword']) {
                $raw = false;
                $where['group_id']=$formget['group_id'];
                $where['group_type'] = 1;
            } else if  (isset($formget['group_id']) && $formget['keyword']) {
                $where[0] = "group_id = ? and ( account like ? or nickname like ? ) and group_type = ?";
                $where[1] = [$formget['group_id'], '%'.$formget['keyword'].'%','%'.$formget['keyword'].'%','1'];
                $raw = true;
            }
        }


        $account_model = new model\AccountModel($this->service);
        $total = $account_model->companyCount($where,$raw);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];

        $pageLink = urlGen($req,$path,[],true);
        $per_page = 20;
        $page = $this->page($pageLink,$total,$per_page);
        $lists = $account_model->companyLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

        //ng_func_privilege_check($req->company_id,$this->sessions['admin_uid'],'index');

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'user',
        ];

        if ($lists) {

            foreach ($lists as $key=>$val) {
                $operater_url = array_merge($query,['act'=>'company_edit','uid'=>$val['id']]);
                $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                $operater_url = array_merge($query,['act'=>'company_delete','uid'=>$val['id']]);
                $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                if ($lists[$key]['expire_time']) {
                    if (time()-$lists[$key]['expire_time'] >0 ) {
                        $lists[$key]['status']=10;
                    }
                }



            }
            $operater_url = array_merge($query,['act'=>'company_delete']);
            $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
        }

        $operater_url = array_merge($query,['act'=>'company_add']);
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
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','user/company');
    }

    /**
     * @name 子账号列表
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function company_listsAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);

        $where =[];
        $raw = false;

        if ($req->request_method == 'POST') {
            $formget = $req->post_datas['formget'];
        } else {
            $keyword = urldecode($req->query_datas['keyword']);
            $group_id = urldecode($req->query_datas['group_id']);
            $formget['keyword'] = $keyword;
            $formget['group_id'] = $group_id;
        }

        if ($formget) {
            if ($formget['group_id'] && !$formget['keyword']) {
                $raw = false;
                $where['group_id']=$formget['group_id'];
            } else if  (!isset($formget['group_id']) && $formget['keyword']) {
                $where[0] = "( account like ? or nickname like ? )";
                $where[1] = ['%'.$formget['keyword'].'%','%'.$formget['keyword'].'%'];
                $raw = true;
            } else if  (isset($formget['group_id']) && $formget['keyword']) {
                $where[0] = "group_id = ? and ( account like ? or nickname like ? )";
                $where[1] = [$formget['group_id'], '%'.$formget['keyword'].'%','%'.$formget['keyword'].'%'];
                $raw = true;
            }
        }


        $account_model = new model\AccountModel($this->service);
        $total = $account_model->companyCount($where,$raw);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];

        $pageLink = urlGen($req,$path,[],true);
        $per_page = 20;
        $page = $this->page($pageLink,$total,$per_page);
        $lists = $account_model->companyLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

        //ng_func_privilege_check($req->company_id,$this->sessions['admin_uid'],'index');

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'user',
        ];

        if ($lists) {

            foreach ($lists as $key=>$val) {
                $operater_url = array_merge($query,['act'=>'company_edit','uid'=>$val['id']]);
                $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                $operater_url = array_merge($query,['act'=>'company_delete','uid'=>$val['id']]);
                $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                if ($lists[$key]['expire_time']) {
                    if (time()-$lists[$key]['expire_time'] >0 ) {
                        $lists[$key]['status']=10;
                    }
                }

//                if ($lists[$key]['avatar'] != 'default') {
//                    $cdn_prefix = $this->getCdnHost();
//                    $lists[$key]['avatar'] = $cdn_prefix.'/'.$lists[$key]['avatar'];
//                }


            }
            $operater_url = array_merge($query,['act'=>'company_delete']);
            $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
        }

        $operater_url = array_merge($query,['act'=>'company_add']);
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
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','user/company');
    }

    /**
     * @name 子账号删除
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function company_deleteAction(RequestHelper $req,array $preData)
    {

        if ($req->request_method=='POST') {
            $remove_uids = $req->post_datas['ids'];
        } else {
            $request_uid = $req->query_datas['uid'];
            $remove_uids = [$request_uid];
        }

        $flag = true;
        if (!empty($remove_uids)) {
            $account_model = new model\AccountModel($this->service);
            foreach ($remove_uids as $remove_id) {
                $where = ['id'=>$remove_id];
                $res = $account_model->deleteCompanyAccount($where);
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
     * @name 子账号添加
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function company_addAction(RequestHelper $req,array $preData)
    {
        try {
            //返回地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'user',
                'act'=>'company'
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

            $status = true;
            $mess = '成功';
            $data = [
                'cate_name'=>'运营者',
                'op'=>'add',
                'cate_index_url'=>$cate_index_url,
                'asset_upload_url'=>$asset_upload_url,
            ];
            $account_model = new model\AccountModel($this->service);
            $current_count = $account_model->companyCount(['group_id'=>$req->company_id,'group_type'=>1]);

            $hook_model = new model\HookModel($this->service);
            $hook_model->assert_max_sub_limit($req->company_id,$current_count);

            if($req->request_method == 'POST') {
                $post = $req->post_datas['post'];

                if ($post) {


                    //正常的编辑
                    $map = [];
                    if ($post['account'] && preg_match('/\w{5,16}/is',$post['account'])) {
                        $map['account'] = $post['account'];
                    } else {
                        throw new \Exception('账号不对。');
                    }
                    $check_account_where = [
                        'account'=>$map['account'],
                    ];
                    $exist = $account_model->getCompanyAccount($check_account_where);
                    if ($exist) {
                        throw new \Exception('账号已经存在');
                    }



                    if ($post['nickname'] && (mb_strlen($post['nickname'],'UTF-8')>=2 && mb_strlen($post['nickname'],'UTF-8')<=10)) {
                        $map['nickname'] = $post['nickname'];
                    } else {
                        throw new \Exception('昵称不对。');
                    }

                    //密码
                    if (!$post['newpassword'] || !$post['comfirm_password']) {
                        throw new \Exception('密码必须填。');
                    } else if($post['newpassword']!=$post['comfirm_password']) {
                        throw new \Exception('错认密码错误。');
                    } else {
                        $slat = substr(getRandomStr(),0,6);
                        $map['password'] = md5($post['newpassword'].$slat);
                        $map['slat'] =  $slat;
                    }

                    if ($post['contact_user']) {
                        $map['contact_user'] = $post['contact_user'];
                    } else {
                        throw new \Exception('联系人不为空。');
                    }

                    if ($post['contact_phone']) {
                        $map['contact_phone'] = $post['contact_phone'];
                    } else {
                        throw new \Exception('联系人电话不为空。');
                    }

                    if ($post['desc']) {
                        $map['desc'] = htmlspecialchars($post['desc']);
                    }
                    if($post['alias']) {
                        $map['alias'] = $post['alias'];
                    }

                    $map['status'] = $post['status'];
                    if ($post['expire_time']) {
                        $map['expire_time'] = strtotime($post['expire_time']);
                    } else {
                        $map['expire_time'] = 0;
                    }
                    $map['avatar'] = $post['avatar'];
                    $map['hash_val'] = substr(md5($map['group_id'].$map['account']),8,16);
                    $map['ctime'] = time();
                    $map['mtime'] = time();

                    //管理员添加
                    $admin_opteration = [
                        'type'=>'manager',
                        'uid'=>$this->sessions['manager_uid'],
                        'name'=>$this->sessions['manager_name'],
                    ];
                    $map['operation'] = ng_mysql_json_safe_encode($admin_opteration);

                    $map['group_id'] = $req->company_id;
                    $map['group_type'] = 1;
                    $flag = $account_model->addCompanyAccount($map);
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

            return $this->render($status,$mess,$data,'template','user/company_edit');
        }
    }

    /**
     * @name 子账号编辑
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function company_editAction(RequestHelper $req,array $preData)
    {
        $request_uid = $req->query_datas['uid'];
        try {
            $account_model = new model\AccountModel($this->service);
            if ($request_uid) {
                //返回地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'user',
                    'act'=>'company'
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
                    'admin_uid'=>$request_uid,
                ];
                $asset_upload_url = urlGen($req,$path,$query,true);


                $admin_account = $account_model->getCompanyAccount(['id'=>$request_uid]);
                if (!$admin_account) {
                    throw new \Exception('账号不存在');
                }

                if (!$admin_account['expire_time']) {
                    $admin_account['expire_time'] = '';
                } else {
                    $admin_account['expire_time'] = date('Y-m-d',$admin_account['expire_time']);
                }
                if ($admin_account['desc']) {
                    $admin_account['desc'] = htmlspecialchars_decode($admin_account['desc']);
                }



                $data = [
                    'uid'=>$request_uid,
                    'admin_uid'=>$request_uid,
                    'cate_index_url'=>$cate_index_url,
                    'asset_upload_url'=>$asset_upload_url,
                    'cate_name'=>'运营者',
                    'admin_account'=>$admin_account,
                ];
                $status = true;
                $mess = '成功';

                if($req->request_method == 'POST') {
                    $post = $req->post_datas['post'];

                    if ($post) {
                        if($post['uid']!=$request_uid) {
                            throw new \Exception('用户名uid不对应。');
                        }
                        //正常的编辑
                        $map = [];
                        if ($post['account'] && preg_match('/\w{5,16}/is',$post['account'])) {
                            $map['account'] = $post['account'];
                        } else {
                            throw new \Exception('账号不对。');
                        }
                        $check_account_where = [
                            'account'=>$map['account'],
                        ];
                        $exist = $account_model->getCompanyAccount($check_account_where);
                        if ($exist && $post['uid']!=$exist['id']) {
                            throw new \Exception('账号已经存在');
                        }



                        if ($post['nickname'] && (mb_strlen($post['nickname'],'UTF-8')>=2 && mb_strlen($post['nickname'],'UTF-8')<=10)) {
                            $map['nickname'] = $post['nickname'];
                        } else {
                            throw new \Exception('昵称不对。');
                        }

                        //密码
                        if ($post['newpassword'] || $post['comfirm_password']) {
                            if($post['newpassword']!=$post['comfirm_password']) {
                                throw new \Exception('错认密码错误。');
                            } else {
                                $slat = substr(getRandomStr(),0,6);
                                $map['password'] = md5($post['newpassword'].$slat);
                                $map['slat'] =  $slat;
                            }
                        }
                        

                        if (isset($post['status'])) {
                            $map['status'] = $post['status'];
                        }

                        if ($post['expire_time']) {
                            $map['expire_time'] = strtotime($post['expire_time']);
                        }
                        if ($post['contact_user']) {
                            $map['contact_user'] = $post['contact_user'];
                        } else {
                            throw new \Exception('联系人不为空。');
                        }

                        if ($post['contact_phone']) {
                            $map['contact_phone'] = $post['contact_phone'];
                        } else {
                            throw new \Exception('联系人电话不为空。');
                        }

                        if ($post['desc']) {
                            $map['desc'] = htmlspecialchars($post['desc']);
                        }
                        if($post['alias']) {
                            $map['alias'] = $post['alias'];
                        }

                        $map['avatar'] = $post['avatar'];
                        $map['mtime'] = time();

                        $save_where = [
                            'group_id'=>$req->company_id,
                            'id'=> $post['uid'],
                        ];

                        $flag = $account_model->saveCompanyAccount($save_where,$map);
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
            return $this->render($status,$mess,$data,'template','user/company_edit');
        }

    }

    /**
     * @name 修改用户信息
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function company_userinfoAction(RequestHelper $req,array $preData)
    {
        $sessions = $session = $this->service->getSession();
        $request_uid = $sessions->get('manager_uid');
        try {
            $account_model = new model\AccountModel($this->service);
            if ($request_uid) {
                //返回地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'user',
                    'act'=>'company_userinfo'
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
                    'admin_uid'=>$request_uid,
                ];
                $asset_upload_url = urlGen($req,$path,$query,true);


                $admin_account = $account_model->getCompanyAccount(['id'=>$request_uid]);
                if (!$admin_account) {
                    throw new \Exception('账号不存在');
                }

                if (!$admin_account['expire_time']) {
                    $admin_account['expire_time'] = '';
                } else {
                    $admin_account['expire_time'] = date('Y-m-d',$admin_account['expire_time']);
                }
                if (!$admin_account['desc']) {
                    $admin_account['desc'] = htmlspecialchars_decode($admin_account['desc']);
                }



                $data = [
                    'uid'=>$request_uid,
                    'admin_uid'=>$request_uid,
                    'cate_index_url'=>$cate_index_url,
                    'asset_upload_url'=>$asset_upload_url,
                    'cate_name'=>'运营者',
                    'admin_account'=>$admin_account,
                    'is_self'=>1,
                ];
                $status = true;
                $mess = '成功';

                if($req->request_method == 'POST') {
                    $post = $req->post_datas['post'];

                    if ($post) {
                        if($post['uid']!=$request_uid) {
                            throw new \Exception('用户名uid不对应。');
                        }
                        //正常的编辑
                        $map = [];
                        if ($post['account'] && preg_match('/\w{5,16}/is',$post['account'])) {
                            $map['account'] = $post['account'];
                        } else {
                            throw new \Exception('账号不对。');
                        }
                        $check_account_where = [
                            'account'=>$map['account'],
                        ];
                        $exist = $account_model->getCompanyAccount($check_account_where);
                        if ($exist && $post['uid']!=$exist['id']) {
                            throw new \Exception('账号已经存在');
                        }


                        if ($post['nickname'] && (mb_strlen($post['nickname'],'UTF-8')>=2 && mb_strlen($post['nickname'],'UTF-8')<=10)) {
                            $map['nickname'] = $post['nickname'];
                        } else {
                            throw new \Exception('昵称不对。');
                        }

                        //密码
                        if ($post['newpassword'] || $post['comfirm_password']) {
                            if($post['newpassword']!=$post['comfirm_password']) {
                                throw new \Exception('错认密码错误。');
                            } else {
                                $slat = substr(getRandomStr(),0,6);
                                $map['password'] = md5($post['newpassword'].$slat);
                                $map['slat'] =  $slat;
                            }
                        }


                        if ($post['status']) {
                            $map['status'] = $post['status'];
                        }

                        if ($post['expire_time']) {
                            $map['expire_time'] = strtotime($post['expire_time']);
                        }
                        if ($post['contact_user']) {
                            $map['contact_user'] = $post['contact_user'];
                        } else {
                            throw new \Exception('联系人不为空。');
                        }

                        if ($post['contact_phone']) {
                            $map['contact_phone'] = $post['contact_phone'];
                        } else {
                            throw new \Exception('联系人电话不为空。');
                        }

                        if ($post['desc']) {
                            $map['desc'] = htmlspecialchars($post['desc']);
                        }
                        if($post['alias']) {
                            $map['alias'] = $post['alias'];
                        }

                        $map['avatar'] = $post['avatar'];
                        $map['mtime'] = time();

                        $save_where = [
                            'id'=> $post['uid'],
                        ];
                        $flag = $account_model->saveCompanyAccount($save_where,$map);
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
            return $this->render($status,$mess,$data,'template','user/company_edit');
        }
    }


     //前端用户


    /**
     * @name 前端用户列表
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function frontend_userAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';

        $nav_data = $this->nav_default($req,$preData);

        $company_id = $req->company_id;

        $where =[];
        $raw = false;

        if ($req->request_method == 'POST') {
            $formget = $req->post_datas['formget'];
            $formget['company_id'] = $company_id;
        } else {
            $keyword = urldecode($req->query_datas['keyword']);
            $work_id = urldecode($req->query_datas['work_id']);
            $formget['keyword'] = $keyword;
            $formget['company_id'] = $company_id;
            $formget['work_id'] = $work_id;
        }

        if ($formget) {
            $where_raw_key = [];
            $where_raw_val = [];

            if ($formget['company_id']) {
                $where_raw_key[] = "company_id = ? ";
                $where_raw_val[] = $formget['company_id'];
            }
            if ($formget['work_id']) {
                $where_raw_key[] = "work_id = ? ";
                $where_raw_val[] = $formget['work_id'];
            }
            if ($formget['keyword']) {
                $where_raw_key[] = "( sys_uid like ? or username like ? or nickname like ? ) ";
                $where_raw_val[] = " '%'".$formget['keyword']."'%', '%'".$formget['keyword']."'%','%'".$formget['keyword']."'%' ";
            }
            if ($where_raw_key && $where_raw_val ){
                $raw = true;
                $where[0] = implode('and',$where_raw_key);
                $where[1] = [implode(',',$where_raw_val)];
            }


        }


        $account_model = new model\FontendUserModel($this->service);
        $total = $account_model->userCount($where,$raw);

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];

        $pageLink = urlGen($req,$path,[],true);
        $per_page = 20;
        $page = $this->page($pageLink,$total,$per_page);
        $lists = $account_model->userLists($where,['ctime','desc'],$page->Current_page,$per_page,$raw);

        //ng_func_privilege_check($req->company_id,$this->sessions['admin_uid'],'index');

        $path = [
            'mark' => 'manager',
            'bid'  => $req->company_id,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'user',
        ];

        if ($lists) {

            foreach ($lists as $key=>$val) {
                $operater_url = array_merge($query,['act'=>'frontend_user_edit','sys_uid'=>$val['sys_uid']]);
                $lists[$key]['edit_url'] = urlGen($req,$path,$operater_url,true);

                $operater_url = array_merge($query,['act'=>'frontend_user_delete','sys_uid'=>$val['sys_uid']]);
                $lists[$key]['delete_url'] = urlGen($req,$path,$operater_url,true);

                if ($lists[$key]['expire_time']) {
                    if (time()-$lists[$key]['expire_time'] >0 ) {
                        $lists[$key]['status']=10;
                    }
                }


            }
            $operater_url = array_merge($query,['act'=>'frontend_user_delete']);
            $operaters_delete_action =  urlGen($req,$path,$operater_url,true);
        }

        $operater_url = array_merge($query,['act'=>'frontend_user_add']);
        $operaters_add_action =  urlGen($req,$path,$operater_url,true);

        $pagination = $page->show('Manager');

        $data = [
            'total'=>$total,
            'lists' => $lists,
            'add_action_url'=>$operaters_add_action,
            'delete_action_url'=>$operaters_delete_action,
            'pagination' => $pagination,
            'formget'=>$formget,

        ];
        $data = array_merge($nav_data,$data);

        return $this->render($status,$mess,$data,'template','user/frontend_user');
    }

    /**
     * @name 前端用户删除
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function frontend_user_deleteAction(RequestHelper $req,array $preData)
    {

        if ($req->request_method=='POST') {
            $remove_uids = $req->post_datas['ids'];
        } else {
            $request_uid = $req->query_datas['sys_uid'];
            $remove_uids = [$request_uid];
        }

        $flag = true;
        if (!empty($remove_uids)) {
            $account_model = new model\FontendUserModel($this->service);
            foreach ($remove_uids as $remove_id) {
                $where = ['sys_uid'=>$remove_id];
                $res = $account_model->deleteUser($where);
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
     * @name 前端用户添加
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function frontend_user_addAction(RequestHelper $req,array $preData)
    {
        try {
            //返回地址
            $path = [
                'mark' => 'manager',
                'bid'  => $req->company_id,
                'pl_name'=>'manager',
            ];
            $query = [
                'mod'=>'user',
                'act'=>'frontend_user'
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

            $status = true;
            $mess = '成功';
            $data = [
                'cate_name'=>'终端用户',
                'op'=>'add',
                'cate_index_url'=>$cate_index_url,
                'asset_upload_url'=>$asset_upload_url,
                'admin_uid'=>$this->sessions['manager_uid'],
            ];

            if($req->request_method == 'POST') {
                $post = $req->post_datas['post'];

                if ($post) {
                    $account_model = new model\FontendUserModel($this->service);
                    //正常的编辑
                    $map = [];
                    if ($post['username'] && preg_match('/\w{3,16}/is',$post['username'])) {
                        $map['username'] = $post['username'];
                    } else {
                        throw new \Exception('用户名不对。');
                    }

                    $map['company_id'] = $req->company_id;

                    if ($post['work_id'] && preg_match('/\w{8,16}/is',$post['work_id'])) {
                        $map['work_id'] = $post['work_id'];
                    } else {
                        throw new \Exception('业务id格式不对。');
                    }

                    if ($post['nickname'] && (mb_strlen($post['nickname'],'UTF-8')>=2 && mb_strlen($post['nickname'],'UTF-8')<=10)) {
                        $map['nickname'] = $post['nickname'];
                    } else {
                        throw new \Exception('昵称不对。');
                    }

                    //密码
                    if($post['newpassword']!=$post['comfirm_password']) {
                        throw new \Exception('错认密码错误。');
                    } else {
                        $map['password'] = md5($post['newpassword']);
                    }

                    $map['avatar'] = $post['avatar'];
                    $map['openid'] = $post['openid'];
                    $map['unionid'] = $post['unionid'];
                    $map['sex'] = $post['sex'];
                    $map['comeform'] = $post['comeform'];
                    $map['status'] = $post['status'];

                    if ($post['config']) {
                        $map['config'] = ng_mysql_json_safe_encode($post['config']);
                    }

                    if ($post['detail']) {
                        $map['detail'] = ng_mysql_json_safe_encode($post['detail']);
                    }


                    $flag = $account_model->addUser($map);
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

            return $this->render($status,$mess,$data,'template','user/frontend_user_edit');
        }
    }

    /**
     * @name 前端用户修改
     * @priv ask
     * @param RequestHelper $req
     * @param array $preData
     * @return \libs\asyncme\ResponeHelper
     */
    public function frontend_user_editAction(RequestHelper $req,array $preData)
    {
        $request_uid = $req->query_datas['sys_uid'];
        try {
            $rel_model = new model\FontendUserModel($this->service);
            if ($request_uid) {
                //返回地址
                $path = [
                    'mark' => 'manager',
                    'bid'  => $req->company_id,
                    'pl_name'=>'manager',
                ];
                $query = [
                    'mod'=>'user',
                    'act'=>'frontend_user'
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
                    'admin_uid'=>$this->sessions['manager_uid'],
                ];
                $asset_upload_url = urlGen($req,$path,$query,true);


                $rel_info = $rel_model->userInfo(['sys_uid'=>$request_uid]);
                if (!$rel_info) {
                    throw new \Exception('用户不存在');
                }

                if (!$rel_info['config']) {
                    $rel_info['config'] = htmlspecialchars_decode($rel_info['config']);
                }


                $data = [
                    'uid'=>$request_uid,
                    'admin_uid'=>$this->sessions['manager_uid'],
                    'cate_index_url'=>$cate_index_url,
                    'asset_upload_url'=>$asset_upload_url,
                    'cate_name'=>'终端用户',
                    'obj_rel'=>$rel_info,
                ];
                $status = true;
                $mess = '成功';

                if($req->request_method == 'POST') {
                    $post = $req->post_datas['post'];

                    if ($post) {
                        if($post['sys_uid']!=$request_uid) {
                            throw new \Exception('用户名uid不对应。');
                        }
                        //正常的编辑
                        $map = [];
                        if ($post['username'] && preg_match('/\w{3,16}/is',$post['username'])) {
                            $map['username'] = $post['username'];
                        } else {
                            throw new \Exception('用户名不对。');
                        }

                        $map['company_id'] = $req->company_id;

                        if ($post['work_id'] && preg_match('/\w{8,16}/is',$post['work_id'])) {
                            $map['work_id'] = $post['work_id'];
                        } else {
                            throw new \Exception('业务id格式不对。');
                        }

                        if ($post['nickname'] && (mb_strlen($post['nickname'],'UTF-8')>=2 && mb_strlen($post['nickname'],'UTF-8')<=10)) {
                            $map['nickname'] = $post['nickname'];
                        } else {
                            throw new \Exception('昵称不对。');
                        }

                        //密码
                        if (!$post['password'] && ($post['newpassword'] || $post['comfirm_password'])) {
                            throw new \Exception('原始密码必须填。');
                        } else if($post['password']) {
                            if($rel_info['password']==md5($post['password'])) {

                                if($post['newpassword']!=$post['comfirm_password']) {
                                    throw new \Exception('错认密码错误。');
                                } else {
                                    $map['password'] = md5($post['newpassword'].$slat);
                                }

                            } else {
                                throw new \Exception('原始密码错误。');
                            }
                        }

                        $map['avatar'] = $post['avatar'];
                        $map['openid'] = $post['openid'];
                        $map['unionid'] = $post['unionid'];
                        $map['sex'] = $post['sex'];
                        $map['comeform'] = $post['comeform'];
                        $map['status'] = $post['status'];

                        if ($post['config']) {
                            $map['config'] = ng_mysql_json_safe_encode($post['config']);
                        }

                        if ($post['detail']) {
                            $map['detail'] = ng_mysql_json_safe_encode($post['detail']);
                        }

                        $save_where = [
                            'sys_uid'=> $post['sys_uid'],
                        ];
                        $flag = $rel_model->saveUser($save_where,$map);
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
            return $this->render($status,$mess,$data,'template','user/frontend_user_edit');
        }

    }

}