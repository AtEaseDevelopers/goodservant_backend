<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Offline documents in the driver app: messages when there is no signal,
 * the Offline documents page, the Home banner and the End Trip / Logout
 * guards.
 */
class AddOfflineDocumentsMobileTranslations extends Migration
{
    private $translations = [
        'app.offline.error.needsetup' => ['en' => 'No signal. Connect to the internet once after login before working offline.', 'ms' => 'Tiada isyarat. Sambung ke internet sekali selepas log masuk sebelum bekerja di luar talian.', 'zh' => '没有信号。登录后请先连接一次网络，才能离线工作。'],
        'app.offline.error.clock' => ['en' => 'No signal, and the phone\'s date or time is wrong. Correct the phone\'s date and time first.', 'ms' => 'Tiada isyarat, dan tarikh atau masa telefon salah. Betulkan tarikh dan masa telefon dahulu.', 'zh' => '没有信号，而且手机的日期或时间不正确。请先更正手机的日期和时间。'],
        'app.offline.toast.saved' => ['en' => 'No signal: saved on the phone. It uploads by itself when signal is back.', 'ms' => 'Tiada isyarat: disimpan dalam telefon. Ia akan dimuat naik sendiri apabila ada isyarat.', 'zh' => '没有信号：已保存在手机，有信号时会自动上传。'],
        'app.offline.dialog.unconfirmed' => ['en' => 'No reply from the server. It may already be saved. Tap Create again when there is signal: it will not be created twice.', 'ms' => 'Tiada jawapan daripada pelayan. Ia mungkin sudah disimpan. Tekan Cipta sekali lagi apabila ada isyarat: ia tidak akan dicipta dua kali.', 'zh' => '服务器没有回应，可能已经保存。有信号时再按一次创建，不会重复创建。'],
        'app.offline.title.pending' => ['en' => 'Offline documents', 'ms' => 'Dokumen luar talian', 'zh' => '离线单据'],
        'app.offline.text.waiting' => ['en' => 'waiting to upload', 'ms' => 'menunggu dimuat naik', 'zh' => '等待上传'],
        'app.offline.button.uploadnow' => ['en' => 'Upload now', 'ms' => 'Muat naik sekarang', 'zh' => '立即上传'],
        'app.offline.button.print' => ['en' => 'Print', 'ms' => 'Cetak', 'zh' => '打印'],
        'app.offline.button.cancel' => ['en' => 'Cancel document', 'ms' => 'Batal dokumen', 'zh' => '取消单据'],
        'app.offline.dialog.cancel' => ['en' => 'Cancel this document? It is kept on record as cancelled.', 'ms' => 'Batalkan dokumen ini? Ia disimpan dalam rekod sebagai dibatalkan.', 'zh' => '确定取消这张单据？记录会保留为已取消。'],
        'app.offline.status.waiting' => ['en' => 'Waiting to upload', 'ms' => 'Menunggu dimuat naik', 'zh' => '等待上传'],
        'app.offline.status.uploaded' => ['en' => 'Uploaded', 'ms' => 'Sudah dimuat naik', 'zh' => '已上传'],
        'app.offline.status.cancelled' => ['en' => 'Cancelled', 'ms' => 'Dibatalkan', 'zh' => '已取消'],
        'app.offline.text.renumbered' => ['en' => 'Number already used, new number', 'ms' => 'Nombor sudah digunakan, nombor baru', 'zh' => '号码已被使用，新号码'],
        'app.offline.dialog.renumbered' => ['en' => 'These numbers were already used, so the server gave new ones. Tell the customer the new number and reprint if needed:', 'ms' => 'Nombor ini sudah digunakan, jadi pelayan memberi nombor baru. Beritahu pelanggan nombor baru dan cetak semula jika perlu:', 'zh' => '这些号码已被使用，服务器给了新号码。请告诉客户新号码，需要时重新打印：'],
        'app.offline.dialog.blocked.endtrip' => ['en' => 'Some offline documents are not uploaded yet. Connect to the internet and upload them before ending the trip.', 'ms' => 'Ada dokumen luar talian yang belum dimuat naik. Sambung ke internet dan muat naik dahulu sebelum tamat perjalanan.', 'zh' => '还有离线单据未上传。请连接网络上传后再结束行程。'],
        'app.offline.dialog.blocked.logout' => ['en' => 'Some offline documents are not uploaded yet. Connect to the internet and upload them before logging out.', 'ms' => 'Ada dokumen luar talian yang belum dimuat naik. Sambung ke internet dan muat naik dahulu sebelum log keluar.', 'zh' => '还有离线单据未上传。请连接网络上传后再登出。'],
        'app.offline.dialog.otherphone' => ['en' => 'This driver still has offline documents waiting on another phone. Open the app on that phone with internet so they upload.', 'ms' => 'Pemandu ini masih ada dokumen luar talian di telefon lain. Buka aplikasi di telefon itu dengan internet supaya ia dimuat naik.', 'zh' => '这位司机在另一部手机还有离线单据未上传。请在那部手机连接网络打开应用，让单据上传。'],
        'app.offline.toast.convertneedssignal' => ['en' => 'Converting a Sales Order needs internet.', 'ms' => 'Menukar Pesanan Jualan memerlukan internet.', 'zh' => '转换销售订单需要网络。'],
        'app.offline.toast.editneedssignal' => ['en' => 'Editing a Sales Order needs internet.', 'ms' => 'Mengubah Pesanan Jualan memerlukan internet.', 'zh' => '修改销售订单需要网络。'],
        'app.offline.text.empty' => ['en' => 'No offline documents.', 'ms' => 'Tiada dokumen luar talian.', 'zh' => '没有离线单据。'],
        'app.offline.toast.alluploaded' => ['en' => 'All offline documents are uploaded.', 'ms' => 'Semua dokumen luar talian sudah dimuat naik.', 'zh' => '所有离线单据已上传。'],
        'app.offline.toast.stillwaiting' => ['en' => 'No signal yet. They will upload when signal is back.', 'ms' => 'Belum ada isyarat. Ia akan dimuat naik apabila ada isyarat.', 'zh' => '还没有信号，有信号时会自动上传。'],
        'app.offline.text.rejected' => ['en' => 'Not accepted by the server', 'ms' => 'Tidak diterima oleh pelayan', 'zh' => '服务器未接受'],
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
            $current = (int) DB::table('mobile_translation_versions')->where('language_id', $languageId)->value('version');
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => $current + 1, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
