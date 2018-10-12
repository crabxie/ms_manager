<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/9/5
 * Time: 下午5:19
 */

namespace manager;

use libs\asyncme\NgJsonHtml;
use libs\asyncme\RequestHelper;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class Test extends PermissionBase
{

    public function indexAction(RequestHelper $req,array $preData) {
        try {
            $jsonHtml = new NgJsonHtml();
            $jsonHtml->setData($this->getJson());
            $jsonHtml->parse();
            $inner_html = $jsonHtml->getHtml();
            $inner_js = $jsonHtml->getJs();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }


        $status = true;
        $mess = '成功';
        $data = [
            'inner_html'=>$inner_html,
            'inner_js'=>$inner_js,
            'error'=>$error,
        ];

        return $this->render($status,$mess,$data,'template','test/index');
    }

    protected function getJson()
    {
        $testjson = [
            [
                'type'=>'table',
                'attr'=>['class'=>'table table-bordered'],
                'children'=>[
                    [
                        'type'=>'tr',
                        'attr'=>[],
                        'children'=>
                            [
                                [
                                    'type'=>'th',
                                    'attr'=>['width'=>'80'],
                                    'data'=>'名称',
                                ],
                                [
                                    'type'=>'input',
                                    'wrap'=>'td',
                                    'attr'=>['name'=>'title','value'=>'','method'=>'post','placeholder'=>'请输入名称'],
                                ],
                            ],
                    ],
                    [
                        'type'=>'tr',
                        'attr'=>[],
                        'children'=>
                            [
                                [
                                    'type'=>'th',
                                    'attr'=>['width'=>'80'],
                                    'data'=>'性别',
                                ],
                                [
                                    'type'=>'radio',
                                    'attr'=>['name'=>'sex'],
                                    'wrap_title'=>'td',
                                    'wrap'=>'td',
                                    'wrap_option'=>'',
                                    'options'=>[
                                        '保密'=>['value'=>0,],
                                        '男士'=>['value'=>1,'checked'=>true],
                                        '女士'=>['value'=>2],
                                    ]
                                ],
                            ]
                    ],
                    [
                        'type'=>'tr',
                        'attr'=>[],
                        'children'=>
                            [
                                [
                                    'type'=>'th',
                                    'attr'=>['width'=>'80'],
                                    'data'=>'年级',
                                ],
                                [
                                    'type'=>'select',
                                    'attr'=>['name'=>'grade'],
                                    'wrap'=>'td',
                                    'wrap_option'=>'',
                                    'options'=>[
                                        '未知'=>['value'=>0,],
                                        '小学'=>['value'=>1,'selected'=>true],
                                        '初中'=>['value'=>2],
                                        '高中'=>['value'=>3],
                                        '大学'=>['value'=>4],
                                    ]
                                ],
                            ]
                    ],
                    [
                        'type'=>'tr',
                        'attr'=>[],
                        'children'=>
                            [
                                [
                                    'type'=>'th',
                                    'attr'=>['width'=>'80'],
                                    'data'=>'图片',
                                ],
                                [
                                    'type'=>'upload',
                                    'btn_title'=>'上传',
                                    'name'=>'thumb',
                                    'data-type'=>'img',
                                    'attr'=>['data-action'=>'xxx','id'=>'file_upload'],
                                    'wrap'=>'td',
                                    'preview_id'=>'file_upload_preview',

                                ],
                            ]
                    ],

                ]


            ]

        ];
        return $testjson;
    }
}

