<?php

namespace App\Services;

use App\Components\Helper\LogHelper;
use App\Models\BalanceLog;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Auth;

class InvoiceService
{
    /**
     * 使用事务保存发票申请
     * @param $row
     * @param $assocRows
     * @return mixed
     */
    public static function transSaveInvoice($row, $assocRows)
    {
        //先插up_invoice表 ， 再插up_invoice_balance_log_assoc表
        $transactionResult = DB::transaction(function () use ($row, $assocRows) {
            $invoice = new Invoice();
            $invoice->account_id = $row['account_id'];
            $invoice->user_id = $row['user_id'];
            $invoice->agencyid = $row['agencyid'];
            $invoice->invoice_type = $row['invoice_type'];
            $invoice->title = $row['title'];
            $invoice->money = $row['money'];
            $invoice->address = $row['address'];
            $invoice->receiver = $row['receiver'];
            $invoice->tel = $row['tel'];
            $invoice->comment = $row['comment'];
            //第一次创建时间和更新时间一样
            $invoice->create_time = $invoice->update_time = $row['create_time'];
            $invoice->status = $row['status'];

            $result = $invoice->save();

            if (!$result) {
                LogHelper::warning('submit invoice error');
                DB::rollBack();
                return false;
            }

            $invoice->balancelogs()->sync($assocRows);

            return true;
        });

        return $transactionResult;
    }

    /**
     * 提交发票申请
     * @param $params
     * @return mixed
     */
    public static function invoiceStore($params)
    {
        //还得判断发票申请的ID是否已经申请过
        $ids = explode(',', $params['ids']);

        $assocRow = DB::table('invoice_balance_log_assoc')
            ->where('status', '=', Invoice::STATUS_TYPE_APPLICATION)
            ->whereIn('balance_log_id', $ids)
            ->select('balance_log_id')
            ->get();

        if (!empty($assocRow)) {
            LogHelper::warning('The invoice ' . implode(',', $ids) . ' has been applied for');
            return 5031;
        }

        if (Auth::user()->account->isBroker()) {
            $agencyId = Auth::user()->account->broker->agency->agencyid;
        } else {
            $agencyId = Auth::user()->account->client->agency->agencyid;
        }
        $money = BalanceLog::whereIn('id', $ids)->sum('amount');
        $address = json_encode(
            array(
                'prov' => $params['prov'],
                'city' => $params['city'],
                'dist' => $params['dist'],
                'addr' => $params['address'],
            )
        );

        //新建Invoice数据行
        $row = [
            'account_id' => Auth::user()->account->account_id,
            'user_id' => Auth::user()->user_id,
            'agencyid' => $agencyId,
            'invoice_type' => $params['type'],
            'title' => $params['title'],
            'money' => $money,
            'address' => $address,
            'receiver' => $params['receiver'],
            'tel' => $params['tel'],
            'comment' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'status' => Invoice::STATUS_TYPE_APPLICATION
        ];

        $assocRows = [];
        foreach ($ids as $balanceId) {
            $assocRows[$balanceId] = [
                'status' => 1,
            ];
        }

        $transactionResult = self::transSaveInvoice($row, $assocRows);
        if (!$transactionResult) {
            return 5001;  // @codeCoverageIgnore
        }

        return true;
    }
}
