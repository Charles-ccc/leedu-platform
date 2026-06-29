<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2;

use App\Leedu\ServiceV2\Dao\UserDao;
use App\Leedu\ServiceV2\Dao\OrderDao;
use App\Leedu\ServiceV2\Dao\OtherDao;
use App\Leedu\ServiceV2\Dao\CourseDao;
use App\Leedu\ServiceV2\Dao\CommentDao;
use App\Leedu\ServiceV2\Dao\AgreementDao;
use App\Leedu\ServiceV2\Dao\UserDaoInterface;
use App\Leedu\ServiceV2\Services\UserService;
use App\Leedu\ServiceV2\Dao\OrderDaoInterface;
use App\Leedu\ServiceV2\Dao\OtherDaoInterface;
use App\Leedu\ServiceV2\Services\OrderService;
use App\Leedu\ServiceV2\Services\OtherService;
use App\Leedu\ServiceV2\Dao\CourseDaoInterface;
use App\Leedu\ServiceV2\Services\ConfigService;
use App\Leedu\ServiceV2\Services\CourseService;
use App\Leedu\ServiceV2\Dao\CommentDaoInterface;
use App\Leedu\ServiceV2\Services\CommentService;
use App\Leedu\ServiceV2\Dao\AgreementDaoInterface;
use App\Leedu\ServiceV2\Services\AgreementService;
use App\Leedu\ServiceV2\Services\FullSearchService;
use App\Leedu\ServiceV2\Services\UserServiceInterface;
use App\Leedu\ServiceV2\Services\OrderServiceInterface;
use App\Leedu\ServiceV2\Services\OtherServiceInterface;
use App\Leedu\ServiceV2\Services\ConfigServiceInterface;
use App\Leedu\ServiceV2\Services\CourseServiceInterface;
use App\Leedu\ServiceV2\Services\CommentServiceInterface;
use App\Leedu\ServiceV2\Services\AgreementServiceInterface;
use App\Leedu\ServiceV2\Services\FullSearchServiceInterface;

class ServiceInit
{
    public $dao = [
        OtherDaoInterface::class => OtherDao::class,
        UserDaoInterface::class => UserDao::class,
        CourseDaoInterface::class => CourseDao::class,
        OrderDaoInterface::class => OrderDao::class,
        CommentDaoInterface::class => CommentDao::class,
        AgreementDaoInterface::class => AgreementDao::class,
    ];

    public $service = [
        ConfigServiceInterface::class => ConfigService::class,
        OtherServiceInterface::class => OtherService::class,
        UserServiceInterface::class => UserService::class,
        CourseServiceInterface::class => CourseService::class,
        OrderServiceInterface::class => OrderService::class,
        FullSearchServiceInterface::class => FullSearchService::class,
        CommentServiceInterface::class => CommentService::class,
        AgreementServiceInterface::class => AgreementService::class,
    ];

    public function run()
    {
        foreach ($this->dao as $interface => $class) {
            app()->instance($interface, app()->make($class));
        }

        foreach ($this->service as $interface => $class) {
            app()->instance($interface, app()->make($class));
        }
    }
}
