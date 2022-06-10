<?php

namespace Xww\Leech;

class ContinuousFill
{

    public $opt = [
        'total_amount' => '5000', //总金额
        'base_amount' => '100', //基础金额
        'position_amount' => '100', //总补仓金额
        'position_avg' => '3000', //持仓均价
        'position_volume' => '0.03333333', //总补仓数量
        'position_fee_volume' => '0.00005', //手续费占用数量
        'fee_ratio' => '0.0015', //手续费率
        'min_amount' => '10', //最新金额
        'drop_ratio' => '1.2,2', //触发补仓跌幅比 数列
        'fill_drop_ratio' => '0.6,1.3', //补仓后控制跌幅比 数列
        'latest_fill_price' => '3000', //上次补仓价格
        'depth_price' => '3000', //行情价格
    ];

    public $currentOpt = [];
    public $dropRatios = [];
    public $fillDropRatios = [];
    public $data;

    public function setOpt($opt = [])
    {
        $this->opt = array_merge($this->opt, $opt);
        $this->init();
        $this->generatePreviewData();
    }

    public function init()
    {
        $o = $this->opt;
        $old_position_volume = $o['position_amount'] / $o['position_avg'];
        $position_fee_volume = $old_position_volume * $o['fee_ratio'];
        $o['position_volume'] = $old_position_volume;
        $o['position_fee_volume'] = $position_fee_volume;
        $this->dropRatios = explode(',', $o['drop_ratio']);
        $this->fillDropRatios = explode(',', $o['fill_drop_ratio']);
        $o['drop_ratio'] = $this->dropRatios[0];
        $o['fill_drop_ratio'] = $this->fillDropRatios[0];
        $o['fill_data'] = [
            'fill_price' => $o['position_avg'], //补仓价格
            'fill_volume' => $o['position_volume'], //补仓数量
            'fill_amount' => $o['base_amount'], //补仓的金额
            'fee_volume' => $o['base_amount'] * $o['fee_ratio'], //手续费 数量
            'fill_price_fee_ratio' => '', //补仓价格跌幅比去除手续费
            'fill_price_ratio' => '', //补仓价格跌幅比
            'fill_amount_ratio' => 1, //补仓基础金额比
        ];
        $this->currentOpt = $o;
    }

    //预览生成数据
    public function generatePreviewData()
    {
        $drop_ratios = $this->dropRatios;
        $fill_drop_ratios = $this->fillDropRatios;
        $opt = $this->currentOpt;
        $singleFill = new SingleFill();
        $singleFill->setOpt($opt);
        $next_fill_depth_price = $singleFill->getNextFillDepthPrice();
        $singleFill->updateDepthPrice($next_fill_depth_price);

        $curr_drop_ratio = $drop_ratios[0];
        $curr_fill_drop_ratio = $fill_drop_ratios[0];
        $fill_datas = [];
        $fill_details[] = $opt;
        $fill_sequences = [];
        $index = 0;

        do {
            $fill_data = $singleFill->getFillData();
            if (empty($fill_data)) {
                break;
            }
            $fill_datas[] = $fill_data;
            $o = $fill_details[] = $singleFill->getAfterFillDetails();

            $index++;
            $curr_drop_ratio = $drop_ratios[$index] ?? $curr_drop_ratio;
            $curr_fill_drop_ratio = $fill_drop_ratios[$index] ?? $curr_fill_drop_ratio;
            $o['drop_ratio'] = $curr_drop_ratio;
            $o['fill_drop_ratio'] = $curr_fill_drop_ratio;
            $singleFill->setOpt($o);
            $next_fill_depth_price = $singleFill->getNextFillDepthPrice();
            $singleFill->updateDepthPrice($next_fill_depth_price);
        } while ($singleFill->hasFill());

        $this->data = [
            'fill_sequences' => $fill_sequences,
            'fill_datas' => $fill_datas,
            'fill_details' => $fill_details,
            'next_drop_ratio' => $curr_drop_ratio,
            'next_fill_drop_ratio' => $curr_fill_drop_ratio,
            'next_fill_depth_price' => $next_fill_depth_price,
        ];
    }

    //获取补仓数据
    public function getFillDatas()
    {
        return $this->data['fill_datas'] ?? [];
    }

    //获取补仓 数列
    public function getFillSequences()
    {
        if (isset($this->data['fill_datas'])) {
            $fill_price_fee_ratios = array_column($this->data['fill_datas'], 'fill_price_fee_ratio');
            $fill_price_ratios = array_column($this->data['fill_datas'], 'fill_price_ratio');
            $fill_amount_ratios = array_column($this->data['fill_datas'], 'fill_amount_ratio');
            array_unshift($fill_amount_ratios, 1);
            return [
                'fill_price_fee_ratio_str' => implode(',', array_filter($fill_price_fee_ratios)),
                'fill_price_ratio_str' => implode(',', array_filter($fill_price_ratios)),
                'fill_amount_ratio_str' => implode(',', array_filter($fill_amount_ratios)),
                // 'close_ratio_str' => $this->opt['fill_drop_ratio'],
            ];
        }
        return [];
    }

    public function getFillAd()
    {
        if (isset($this->data['fill_datas'])) {
            $fill_price_fee_ratios = array_column($this->data['fill_datas'], 'fill_price_fee_ratio');
            $fill_price_ratios = array_column($this->data['fill_datas'], 'fill_price_ratio');
            $fill_amount_ratios = array_column($this->data['fill_datas'], 'fill_amount_ratio');
            $len = count($fill_price_fee_ratios);
            $base_amount = $this->opt['base_amount'];
            $close_ratios = explode(',', $this->opt['fill_drop_ratio']);
            $fpfr_sum = 0;
            $fpr_sum = 0;
            $far_sum = 1;
            $close_i = 0;
            $arr = [];
            for ($i = 0; $i < $len; $i++) {
                $add_i = $i + 1;
                if (isset($close_ratios[$add_i])) {
                    $close_i = $add_i;
                }
                $fpfr = $fill_price_fee_ratios[$i];
                $fpr = $fill_price_ratios[$i];
                $far = $fill_amount_ratios[$add_i];
                $cr = $close_ratios[$close_i];
                $fpfr_sum += $fpfr;
                $fpr_sum += $fpr;
                $far_sum += $far;
                $amount = $base_amount * $far_sum;
                $tmp = [
                    'fill_price_fee_ratio_sum' => $fpfr_sum,
                    'fill_price_ratio_sum' => $fpr_sum,
                    'fill_amount_ratio_sum' => $far_sum,
                    'total_amount' => $amount,
                    'close_ratio' => $cr,
                ];
                $arr[] = $tmp;
            }
            return $arr;
        }
    }

    //获取补仓相信数据
    public function getFillDetails()
    {
        return $this->data['fill_details'] ?? [];
    }

    //获取 预览生成原始数据
    public function getPreviewData()
    {
        return $this->data;
    }
}
