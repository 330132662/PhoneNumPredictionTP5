<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\model\PhoneNumM;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
use think\Log;

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
        $start = request()->get('start/s');
        $end = request()->get('end/s');
        $city = request()->get('city/s');
        $province = request()->get('province/s');
        $export = $this->request->get("is_export/d", 0);


        $province = str_replace("省", "", $province);
        $city = str_replace("市", "", $city);
        $cond["prefix"] = array("=", "$start");
        $cond["province"] = array("like", "%$province");
        $cond["city"] = array("like", "%$city");
        $res = PhoneNumM::where($cond)->select();
        $total = PhoneNumM::count();
        $resp["all_count"] = $total;
        if ($res) {
            $telList = [];
            foreach ($res as $k => $v) {
                $telList[$k]['phone'] = $v['phone'] . $end;
                if (!$export) {
                    $telList[$k]['isp'] = $v['province'] . $v['city'] . $v['isp'];
                }
            }
            $resp["list"] = $telList;
            $resp["current_count"] = count($res);
            $resp["all_count"] = $total;
            $resp["download"] = request()->domain() . request()->url() . "&is_export=1";
            if ($export) {
                $this->exportExcel($telList);
            } else {
                $this->success("OK", $resp);

            }
        } else {
            $this->error("没有符合条件的数据", $resp);
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

    private function exportExcel($employeeData)
    {
        try {
            // 创建新的Spreadsheet对象
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // 设置表格标题
            $sheet->setTitle('信息表');

            // 设置表头
            $headers = ['手机号'];
            $columnIndex = 1;

            /*foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($columnIndex++, 1, $header);
            }*/

            // 获取数据

            // 填充数据
            $rowIndex = 1;
            foreach ($employeeData as $employee) {
                $columnIndex = 1;
                foreach ($employee as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $value);
                }
                $rowIndex++;
            }

            // 设置表头样式
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '409EFF']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

            // 设置数据区域样式
            $dataStyle = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            $lastRow = count($employeeData) + 1;
            $sheet->getStyle("A2:F{$lastRow}")->applyFromArray($dataStyle);

            // 设置列宽
            $sheet->getColumnDimension('A')->setWidth(20);
//            $sheet->getColumnDimension('B')->setWidth(20);
            /*  $sheet->getColumnDimension('C')->setWidth(15);
              $sheet->getColumnDimension('D')->setWidth(18);
              $sheet->getColumnDimension('E')->setWidth(15);
              $sheet->getColumnDimension('F')->setWidth(12);*/

            // 设置数字格式（工资列）
            $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('¥#,##0');

            // 设置自动筛选
            $sheet->setAutoFilter("A1:F{$lastRow}");

            // 设置冻结窗格（固定表头）
            $sheet->freezePane('A2');

            // 创建Excel文件
            $writer = new Csv($spreadsheet);
            // 配置分隔符（可选，默认为逗号）
            $writer->setDelimiter("\t");  // 使用制表符分隔（更适合TXT）
            $writer->setEnclosure('');    // 不使用引号包裹值
            $writer->setLineEnding("\r\n"); // Windows格式换行
            $writer->setSheetIndex(0);    // 选择第一个工作表


            // 设置响应头，准备下载
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="信息表_' . date('YmdHis') . '.txt"');
            header('Cache-Control: max-age=0');

            // 输出到浏览器
            $writer->save('php://output');

            // 终止脚本执行
//            exit;


            // 生成保存路径
            /*$uploadDir = __DIR__ . '/public/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = '信息表_' . date('YmdHis') . '.xlsx';
            $filePath = $uploadDir . $fileName;

            // 保存Excel文件到服务器
            $writer->save($filePath);
            $resp["s"] = request()->host() . '/public/uploads/' . $fileName;
            $this->success("", $resp);*/
        } catch (Exception $e) {
            // 错误处理
            header('Content-Type: text/plain; charset=utf-8');
            $s = $e->getMessage() . $e->getTraceAsString();
            echo "导出Excel文件时发生错误: " . $s;
            Log::error($s);
//            $this->error("导出Excel文件时发生错误: " . $e->getMessage());

        }
    }
}
