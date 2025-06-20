<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\model\PhoneNumM;
use think\Db;

/**
 * 示例接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1', "area", "index"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];


    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
    'code':'1',
    'msg':'返回成功'
    })
     */
    public function test()
    {
        $this->success('返回成功', $this->request->param());
    }

    /**
     * 无需登录的接口
     *
     */
    public function test1()
    {
        $this->success('返回成功', ['action' => 'test1']);
    }

    /**
     * 需要登录的接口
     *
     */
    public function test2()
    {
        $this->success('返回成功', ['action' => 'test2']);
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }

    function index()
    {
        $start = request()->get('start/d');
        $end = request()->get('end/d');
        $city = request()->get('city/s');
        $province = request()->get('province/s');
        $province = str_replace("省", "", $province);
        $city = str_replace("市", "", $city);
        $cond["prefix"] = array("=", "$start");
        $cond["province"] = array("like", "%$province");
        $cond["city"] = array("like", "%$city");
        $res = PhoneNumM::where($cond)->select();
        if ($res) {
            $telList = [];
            foreach ($res as $k => $v) {
                $telList[$k]['phone'] = $v['phone'] . $end;
                $telList[$k]['isp'] = $v['province'] . $v['city'] . $v['isp'];
            }

            $this->success("OK", $telList);
        } else {
            $this->error("没有符合条件的数据");
        }


    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
//        $params = $this->request->get("row/a");
        $lv = $this->request->get("level/d", 1);
        $pid = $this->request->get("pid/d", 0);
        if ($pid == 0) {
            $lv = 1;
        } else {
            $lv = 2;
        }
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : null;
            $city = isset($params['city']) ? $params['city'] : null;
        } else {
            $province = $this->request->get('province');
            $city = $this->request->get('city');
        }
        $where = ['pid' => $pid, 'level' => $lv];
        $provincelist = null;
        if ($province !== null) {
            $where['pid'] = $province;
            $where['level'] = 2;
            if ($city !== null) {
                $where['pid'] = $city;
                $where['level'] = 3;
            }
        }
        $provincelist = Db::name('area')->where($where)->field('id as value,shortname')->select();
        $this->success('', $provincelist);

    }
}
