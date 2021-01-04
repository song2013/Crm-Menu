<?php

namespace Crm\Menu\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MenuCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:publish {--brand= : The brand of the menu publish or "all" to publish all brand.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'menu publish to brand database';

    /**
     * @var string
     */
    protected $brandDatabase = '';

    /**
     * @var string
     */
    protected $brandTable = '';

    /**
     * @var string
     */
    protected $permissionTable = '';

    /**
     * @var string
     */
    protected $permissionVersionTable = '';

    /**
     * @var string
     */
    protected $brandPermissionTable = '';

    /**
     * @var string
     */
    protected $sysPermissionTable = '';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->setParams();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $version    = date('Y_m_d_H_i_s');
        $versionLog = [];
        foreach ($this->getBrands() as $brand) {
            try {
                set_default_database_connection($brand->crm_db_name);
                DB::beginTransaction();
                //TODO 删除历史或者删除关联表
                DB::table($this->sysPermissionTable)->update(['enable_status' => 1]);
                $querySql = "INSERT INTO {$this->sysPermissionTable} (
                                SELECT
                                    p.* 
                                FROM
                                    {$this->brandDatabase}.{$this->permissionTable} p
                                    INNER JOIN {$this->brandDatabase}.{$this->brandPermissionTable} b ON p.id = b.permission_id 
                                WHERE
                                    b.brand_id = 1 
                                ) 
                                ON DUPLICATE KEY UPDATE enable_status = 1;";
                DB::statement($querySql);
                DB::commit();
                $versionLog[] = [
                    'version'        => $version,
                    'brand_id'       => $brand->id,
                    'publish_status' => 1,
                    'created_at'     => date('Y-m-d H:i:s')
                ];

            } catch (\Exception $exception) {
                DB::rollBack();
                $versionLog[] = [
                    'version'         => $version,
                    'brand_id'        => $brand->id,
                    'publish_status'  => 0,
                    'publish_message' => $exception->getMessage(),
                    'created_at'      => date('Y-m-d H:i:s'),
                ];

                echo $exception->getMessage();
            }

        }
        DB::table($this->brandDatabase.'.'.$this->permissionVersionTable)->insert($versionLog);

    }

    protected function getBrands()
    {
        $ids = (array) explode(',', $this->option('brand'));

        if (count($ids) === 1 && $ids[0] === 'all') {
            //TODO 所有品牌
            $ids = $this->getAllBrand();
        }

        return $ids;
    }

    protected function getAllBrand()
    {
        //TODO 设置config表名
        set_default_database_connection($this->brandDatabase);

        $brands = DB::table($this->brandTable)
            ->where('enable_status', 1)
            ->select(['id', 'name', 'crm_db_name'])
            ->get()
            ->toArray();

        return $brands;
    }

    protected function setParams()
    {
        $this->brandDatabase          = config('menu.brand_database') ?? 'crm';
        $this->brandTable             = config('menu.brand_table') ?? 'brand';
        $this->permissionTable        = config('menu.permission_table') ?? 'permission';
        $this->permissionVersionTable = config('menu.permission_version_table') ?? 'permission';
        $this->brandPermissionTable   = config('menu.brand_permission_table') ?? 'brand_permission';
        $this->sysPermissionTable     = config('menu.sys_permission_table') ?? 'sys_permission';
    }
}
