<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

use App\Constant\BackendPermission;
use Illuminate\Support\Facades\Route;

// 公开:机构入驻申请(无需登录)
Route::post('/merchant/apply', 'MerchantController@apply');

Route::group([
    'middleware' => ['auth:administrator'],
], function () {
    // 白名单 - 系统配置
    Route::get('/system/config', 'SystemController@config');

    // 平台化:机构管理(仅平台管理员,控制器内校验 merchant_id=0)
    Route::group(['prefix' => 'merchant'], function () {
        Route::get('/', 'MerchantController@index');
        Route::post('/', 'MerchantController@store');
        Route::get('/{id}', 'MerchantController@detail');
        Route::put('/{id}', 'MerchantController@update');
        Route::post('/{id}/audit', 'MerchantController@audit'); // 入驻审核
        // 机构管理员账号
        Route::get('/{id}/admins', 'MerchantController@admins');
        Route::post('/{id}/admins', 'MerchantController@storeAdmin');
    });

    // 平台化:机构自助配置本机构支付宝
    Route::group(['prefix' => 'merchant-alipay'], function () {
        Route::get('/', 'MerchantAlipayController@show');
        Route::post('/', 'MerchantAlipayController@save');
    });

    // 平台化:平台分成分账记录
    Route::get('/profit-share', 'ProfitShareController@index');

    // 平台化:业务员管理 + 提成记录 + 改派 + 提现
    Route::group(['prefix' => 'salesperson'], function () {
        Route::get('/', 'SalespersonController@index');
        Route::post('/', 'SalespersonController@store');
        Route::get('/commissions', 'SalespersonController@commissions');
        Route::post('/reassign', 'SalespersonController@reassign');
        Route::get('/withdrawals', 'SalespersonController@withdrawals');
        Route::post('/withdrawals', 'SalespersonController@withdrawalCreate');
        Route::post('/withdrawals/{id}/process', 'SalespersonController@withdrawalProcess');
        Route::put('/{id}', 'SalespersonController@update');
        Route::post('/{id}/bind', 'SalespersonController@bind');
    });

    // 平台化:机构端 - 本机构待付提成
    Route::group(['prefix' => 'merchant-commission'], function () {
        Route::get('/', 'MerchantCommissionController@index');
        Route::post('/{id}/pay', 'MerchantCommissionController@pay');
    });

    // 平台化:协议模板(芝麻先享合同)
    Route::group(['prefix' => 'agreement-template'], function () {
        Route::get('/', 'AgreementTemplateController@index');
        Route::post('/', 'AgreementTemplateController@store');
    });

    // 平台化:课程审核
    Route::group(['prefix' => 'course-audit'], function () {
        Route::get('/pending', 'CourseAuditController@pending');   // 平台:待审列表
        Route::post('/{id}/audit', 'CourseAuditController@audit'); // 平台:通过/驳回
        Route::post('/{id}/submit', 'CourseAuditController@submit'); // 机构:提交审核
    });

    // 需要权限检查的路由
    Route::group([
        'middleware' => ['backend.sensitive.mask'],
    ], function () {
        // 学员
        Route::group(['prefix' => 'member'], function () {
            Route::get('/courses', 'MemberController@courses')->middleware('mbp:' . BackendPermission::V2_MEMBER_COURSES);
            Route::get('/course/progress', 'MemberController@courseProgress')->middleware('mbp:' . BackendPermission::V2_MEMBER_COURSE_PROGRESS);
            Route::get('/videos', 'MemberController@videos')->middleware('mbp:' . BackendPermission::V2_MEMBER_COURSES);
            Route::delete('/{id}', 'MemberController@destroy')->middleware('mbp:' . BackendPermission::MEMBER_DESTROY);
        });

        // 数据统计
        Route::group(['prefix' => 'stats'], function () {
            Route::get('/transaction', 'StatsController@transaction')->middleware('mbp:' . BackendPermission::STATS_TRANSACTION);
            Route::get('/transaction-top', 'StatsController@transactionTop')->middleware('mbp:' . BackendPermission::STATS_COURSE);
            Route::get('/transaction-graph', 'StatsController@transactionGraph')->middleware('mbp:' . BackendPermission::STATS_TRANSACTION);

            Route::get('/user-paid-top', 'StatsController@userPaidTop')->middleware('mbp:' . BackendPermission::STATS_USER);
            Route::get('/user', 'StatsController@user')->middleware('mbp:' . BackendPermission::STATS_USER);
            Route::get('/user-graph', 'StatsController@userGraph')->middleware('mbp:' . BackendPermission::STATS_USER);
        });
    });
});
