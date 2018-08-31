<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/7/24
 * Time: 下午7:09
 */

namespace manager;

use libs\asyncme\Plugins;
use manager\model;
use libs\asyncme\RequestHelper;
use Delight\Cookie\Cookie;

class Pub extends ManagerBase
{
    public function IndexAction(RequestHelper $req,array $preData)
    {
        $status = true;
        $mess = '成功';
        $data = [
            'test'=>'hell manager!',
            'req'=>$req,
        ];

        return $this->render($status,$mess,$data);
    }

    public function loginAction(RequestHelper $req,array $preData)
    {

        $path = [
            'mark' => 'plugin',
            'bid'  => $req->company_id,
            'pl_name'=>'verification_code',
        ];
        $query = [
            'act'=>'gen',
            'w'=>'248',
            'h'=>50,
        ];
        $vcode_url = urlGen($req,$path,$query,true);

        $error_code = 0;
        $error = '';
        $admin_user_fail = $this->service->getSession()->get('manager_user_fail');



        try {
            $model_rel = new model\AccountModel($this->service);
            $res = $model_rel->getCompanyAccount(['group_id'=>$req->company_id,'group_type'=>0,'status'=>1]);
            if (!$res) {
                $error_code = 1020;
                $error = '运营账号被锁定';
                throw new \Exception($error,$error_code);
            } else {
                $company_name = $res['nickname'];
                $company_version = 'V'.$res['version'];
            }

            if($admin_user_fail>=5) {
                $error_code = 1022;
                $error = '登陆次数过多';
                throw new \Exception($error,$error_code);
            } else {

                if ($req->request_method=='POST') {

                    $post_datas = $req->post_datas;
                    $session_code = $this->service->getSession()->get($req->company_id.'_vcode');


                    if ($post_datas['verify']!=$session_code) {
                        $error_code = 1005;
                        $error = '验证码错误';
                        throw new \Exception($error,$error_code);
                    } else if (!$post_datas['username']) {
                        $error_code = 1001;
                        $error = '用户名不为空';
                        throw new \Exception($error,$error_code);
                    } else if (strlen($post_datas['username'])>16) {
                        $error_code = 1002;
                        $error = '用户名格式错误';
                        throw new \Exception($error,$error_code);
                    } else if (!$post_datas['password']) {
                        $error_code = 1003;
                        $error = '密码不为空';
                        throw new \Exception($error,$error_code);
                    }
                    $admin_account = new model\AccountModel($this->service);
                    $admin_res = $admin_account->getManagerWithName($req->company_id,$post_datas['username']);
                    $flag = true;
                    if ($admin_res && $admin_res['id']!=1) {
                        if ($admin_res['expire_time']) {
                            if (time()>$admin_res['expire_time']) {
                                $error_code = 1010;
                                $error = '账户已过期';
                                throw new \Exception($error,$error_code);
                            }
                        }
                    }
                    if($flag && $admin_res && $admin_res['status']==1) {
                        $flag = $admin_account->checkPass($post_datas['password'],$admin_res['password'],$admin_res['slat']);
                        if (!$flag) {
                            $error_code = 1011;
                            $error = '密码错误';
                            throw new \Exception($error,$error_code);
                        } else {
                            //设定session
                            $cookie = new Cookie('manager_user');
                            $cookie->setValue($admin_res['account']);
                            $cookie->setMaxAge(60 * 60 * 24);
                            $cookie->save();

                            $session = $this->service->getSession();
                            $session->set('manager_uid',$admin_res['id']);
                            $session->set('manager_user',$admin_res['account']);
                            $session->set('manager_name',$admin_res['nickname']);
                            $session->set('manager_avatar',$admin_res['avatar']);
                            $session->set('manager_login_time',time());
                            $session->set('manager_type',$admin_res['group_type']);
                            $session->set('manager_site_title_prefix',$company_name.'_');
                            $session->set('manager_site_version',$company_version);

                            $logInfo = [
                                'type'=>'manager',
                                'ip'=>getIP(),
                                'user_id'=>$admin_res['id'],
                                'account'=>$admin_res['account'],
                                'nickname'=>$admin_res['nickname'],
                                'mess'=>'登陆成功',
                                'flag'=>true,
                            ];

                            $session->set('manager_user_fail',0);
                            $admin_account->sysLog($req->company_id,$logInfo,'pub/login');

                            $bid = $req->company_id;
                            $path = [
                                'mark' => 'manager',
                                'bid'  => $bid,
                                'pl_name'=>'manager',
                            ];
                            $query = [
                                'mod'=>'index',
                                'act'=>'index'
                            ];
                            $sys_url = urlGen($req,$path,$query);

                            $this->redirect($sys_url);

                        }
                    } else {
                        $error_code = 1010;
                        $error = '运营用户不存在';
                        throw new \Exception($error,$error_code);
                    }

                }

                //

            }

        }catch (\Exception $e) {
            $error = $e->getMessage();
            $error_code = $e->getCode();
        }

        if($error_code>0 && $error_code!=1005) {
            //验证码错误不登记
            if ($post_datas['username']) {
                $logInfo = [
                    'type'=>'manager',
                    'ip'=>getIP(),
                    'account'=>$post_datas['username'],
                    'password'=>$post_datas['password'],
                    'mess'=>'登陆失败',
                    'flag'=>false,
                ];
                $admin_account->sysLog($req->company_id,$logInfo,'pub/login');
            }

            $this->service->getSession()->set('manager_user_fail',$admin_user_fail+1);
        }



        $status = true;
        $mess = '成功';
        $cookie_exist = Cookie::exists('manager_user');
        if ($cookie_exist){
            $cookie_val = Cookie::get('manager_user');
        }




        $data = [
            'default_user'=>$cookie_val,
            'form_url' =>'#',
            'vcode_url'=>$vcode_url,
            'error_code'=>$error_code,
            'admin_user_fail'=>$admin_user_fail,
            'allow_try'=>5,
            'company_name'=>$company_name,
            'company_version'=>$company_version,
            'error'=>$error,
        ];

        return $this->render($status,$mess,$data,'template','login');
    }

    public function logoutAction(RequestHelper $req,array $preData)
    {

        $session = $this->service->getSession();
        $session->delete('manager_uid');
        $session->delete('manager_user');
        $session->delete('manager_name');
        $session->delete('manager_avatar');
        $session->delete('manager_login_time');
        $session->delete('manager_type');
        $session->delete('manager_site_title_prefix');
        $session->delete('manager_site_version');

        $bid = $req->company_id;
        $path = [
            'mark' => 'manager',
            'bid'  => $bid,
            'pl_name'=>'manager',
        ];
        $query = [
            'mod'=>'pub',
            'act'=>'login'
        ];
        $sys_url = urlGen($req,$path,$query);

        $this->redirect($sys_url);

    }


}