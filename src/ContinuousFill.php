<?php
class ContinuousFill
{

    public $opt = [
        'total_amount' => '5000', //总金额
        'base_amount' => '100', //基础金额
        'old_position_amount' => '100', //旧的补仓总金额
        'old_position_avg' => '3000', //旧的补仓均价
        'old_position_volume' => '0.03333333', //旧的补仓数量
        'fee_position_volume' => '0.00005', //手续费占用数量
        'fee_ratio' => '0.0015', //手续费率
        'drop_ratio' => '1.2', //触发补仓跌幅比率数列
        'fill_drop_ratio' => '0.6', //补仓后控制跌幅比率数列
        'depth_price' => '3000', //行情价格
    ];

    public $currentOpt = [];
    public $dropRatios = [];
    public $fillDropRatios = [];

    public function setOpt($opt = [])
    {
        $this->opt = array_merge($this->opt, $opt);
        $this->init();
    }

    public function init()
    {
        $o = $this->opt;
        $old_position_volume = $o['old_position_amount'] / $o['old_position_avg'];
        $fee_position_volume = $old_position_volume * $o['fee_ratio'];
        $this->opt['old_position_volume'] = $old_position_volume;
        $this->opt['fee_position_volume'] = $fee_position_volume;
        $this->dropRatios = explode(',', $o['drop_ratio']);
        $this->fillDropRatios = explode(',', $o['fill_drop_ratio']);
        $o['drop_ratio'] = $this->dropRatios[0];
        $o['fill_drop_ratio'] = $this->fillDropRatios[0];
        $this->currentOpt = $o;
    }

    public function getFillList()
    {
    }

    public function getData()
    {
        $drop_ratios = $this->dropRatios;
        $fill_drop_ratios = $this->fillDropRatios;
        $opt = $this->currentOpt;
        $curr_fill_drop_ratio = $fill_drop_ratios[0];
        $singleFill = new SingleFill();
        $fill_datas = [];
        foreach ($drop_ratios as $index => $drop_ratio) {
            if (isset($fill_drop_ratios[$index])) {
                $curr_fill_drop_ratio = $fill_drop_ratios[$index];
            }
            $opt['drop_ratio'] = $drop_ratio;
            $opt['fill_drop_ratio'] = $curr_fill_drop_ratio;
            $singleFill->setOpt($opt);
            $single_data = $singleFill->getData();
            if(empty($single_data['fill_data'])){
                break;
            }else{
                $fill_datas[] = $single_data['fill_data'];
            }
        }
    }
}
