<?php
/**
 * Created by PhpStorm.
 * User: xiequan
 * Date: 2018/9/13
 * Time: 下午4:29
 */

namespace manager;

use libs\asyncme\RequestHelper;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\PHPSQLParserConstants;
use libs\asyncme\Page as Page;

class Work extends PermissionBase
{

    /**
     * @name 首页
     * @priv ask
     */
    public function index()
    {

    }

    /**
     * @name 添加
     * @priv ask
     */
    public function work_add()
    {

    }
    /**
     * @name 编辑
     * @priv ask
     */
    public function work_edit()
    {

    }

    /**
     * @name 子编辑
     * @priv ask
     */
    public function work_subedit()
    {

    }
    /**
     * @name 通用
     * @priv allow
     */
    public function work_common()
    {

    }
}