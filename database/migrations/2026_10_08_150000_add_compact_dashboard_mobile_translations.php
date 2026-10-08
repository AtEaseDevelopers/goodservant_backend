<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Labels for the compact driver dashboard ("Create Quick Action" section
 * title, short "Sequence" button), the new End Trip screen, and the Stock
 * Transfer (stock request) and Task Transfer screens.
 */
class AddCompactDashboardMobileTranslations extends Migration
{
    private $translations = [
        'app.home.button.sequence' => [
            'en' => 'Sequence',
            'ms' => 'Susunan',
            'zh' => '顺序',
        ],
        'app.home.text.createquickaction' => [
            'en' => 'Create Quick Action',
            'ms' => 'Tindakan Pantas Cipta',
            'zh' => '快速创建',
        ],
        'app.paymentmethod.text.cheque' => [
            'en' => 'Cheque',
            'ms' => 'Cek',
            'zh' => '支票',
        ],
        'app.endtrip.text.paymentcollected' => [
            'en' => 'Payment Collected',
            'ms' => 'Bayaran Dikutip',
            'zh' => '已收款项',
        ],
        'app.endtrip.text.stockclearnote' => [
            'en' => 'Stock left on the lorry will be cleared when you end the trip.',
            'ms' => 'Stok yang tinggal di lori akan dikosongkan apabila perjalanan ditamatkan.',
            'zh' => '结束行程时，货车上剩余的库存将被清空。',
        ],
        'app.stockrequest.tab.myrequests' => [
            'en' => 'My Requests',
            'ms' => 'Permintaan Saya',
            'zh' => '我的请求',
        ],
        'app.stockrequest.tab.toapprove' => [
            'en' => 'To Approve',
            'ms' => 'Untuk Diluluskan',
            'zh' => '待我批准',
        ],
        'app.stockrequest.button.requeststock' => [
            'en' => 'Request Stock',
            'ms' => 'Minta Stok',
            'zh' => '请求库存',
        ],
        'app.stockrequest.button.cancelrequest' => [
            'en' => 'Cancel Request',
            'ms' => 'Batal Permintaan',
            'zh' => '取消请求',
        ],
        'app.stockrequest.text.requestfrom' => [
            'en' => 'Request from',
            'ms' => 'Minta daripada',
            'zh' => '请求对象',
        ],
        'app.stockrequest.text.requestedby' => [
            'en' => 'Requested by',
            'ms' => 'Diminta oleh',
            'zh' => '请求人',
        ],
        'app.stockrequest.text.available' => [
            'en' => 'Available: {{1}}',
            'ms' => 'Ada: {{1}}',
            'zh' => '可用：{{1}}',
        ],
        'app.stockrequest.text.norequests' => [
            'en' => 'No requests today',
            'ms' => 'Tiada permintaan hari ini',
            'zh' => '今天没有请求',
        ],
        'app.stockrequest.text.selectdriverfirst' => [
            'en' => 'Select a driver to see the stock on their lorry',
            'ms' => 'Pilih pemandu untuk melihat stok di lorinya',
            'zh' => '请选择司机以查看其货车上的库存',
        ],
        'app.stockrequest.text.nostock' => [
            'en' => 'This driver has no stock',
            'ms' => 'Pemandu ini tiada stok',
            'zh' => '该司机没有库存',
        ],
        'app.stockrequest.dialog.confirmrequest' => [
            'en' => 'Send this stock request?',
            'ms' => 'Hantar permintaan stok ini?',
            'zh' => '发送此库存请求？',
        ],
        'app.stockrequest.dialog.confirmaccept' => [
            'en' => 'Accept this request? The stock will move from your lorry.',
            'ms' => 'Terima permintaan ini? Stok akan dipindahkan dari lori anda.',
            'zh' => '接受此请求？库存将从您的货车转出。',
        ],
        'app.stockrequest.dialog.confirmreject' => [
            'en' => 'Reject this request?',
            'ms' => 'Tolak permintaan ini?',
            'zh' => '拒绝此请求？',
        ],
        'app.stockrequest.dialog.confirmcancel' => [
            'en' => 'Cancel this request?',
            'ms' => 'Batalkan permintaan ini?',
            'zh' => '取消此请求？',
        ],
        'app.customertransfer.tab.transfer' => [
            'en' => 'Transfer',
            'ms' => 'Pindah',
            'zh' => '转移',
        ],
        'app.customertransfer.tab.history' => [
            'en' => 'History',
            'ms' => 'Sejarah',
            'zh' => '记录',
        ],
        'app.customertransfer.button.transfer' => [
            'en' => 'Transfer ({{1}})',
            'ms' => 'Pindah ({{1}})',
            'zh' => '转移 ({{1}})',
        ],
        'app.customertransfer.dialog.confirm' => [
            'en' => 'Transfer the selected customers to {{1}}? They will leave your list for today.',
            'ms' => 'Pindahkan pelanggan yang dipilih kepada {{1}}? Mereka akan dikeluarkan daripada senarai anda untuk hari ini.',
            'zh' => '将所选客户转移给 {{1}}？他们今天将从您的列表中移除。',
        ],
        'app.customertransfer.text.to' => [
            'en' => 'To',
            'ms' => 'Kepada',
            'zh' => '转给',
        ],
        'app.customertransfer.text.from' => [
            'en' => 'From',
            'ms' => 'Daripada',
            'zh' => '来自',
        ],
        'app.customertransfer.text.nohistory' => [
            'en' => 'No transfers today',
            'ms' => 'Tiada pemindahan hari ini',
            'zh' => '今天没有转移记录',
        ],
        'app.customertransfer.text.nocustomers' => [
            'en' => 'No customers',
            'ms' => 'Tiada pelanggan',
            'zh' => '没有客户',
        ],
        'app.customertransfer.toast.selectcustomer' => [
            'en' => 'Please select at least one customer',
            'ms' => 'Sila pilih sekurang-kurangnya seorang pelanggan',
            'zh' => '请至少选择一位客户',
        ],
    ];

    public function up()
    {
        $now = now();

        foreach (['en', 'ms', 'zh'] as $code) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            foreach ($this->translations as $key => $values) {
                DB::table('mobile_translations')->updateOrInsert(
                    ['language_id' => $languageId, 'key' => $key],
                    ['value' => $values[$code], 'updated_at' => $now, 'created_at' => $now]
                );
            }
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => 17, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
