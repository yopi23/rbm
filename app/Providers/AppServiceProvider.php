<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\DetailSparepartPenjualan;
use App\Models\DetailPartServices;
use App\Models\DetailPembelian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use PDO;
use App\Observers\SparepartSaleObserver;  // Perhatikan namespace yang benar
use App\Observers\PartServiceObserver;
use App\Models\PengeluaranOperasional;
use App\Observers\PengeluaranOperasionalObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        view()->composer('*',function($view){
            if(Auth::check()){
                $view->with('this_user',User::join('user_details','user_details.kode_user','=','users.id')->where([['users.id','=',auth()->user()->id]])->get(['users.*','user_details.*','users.id as id_user'])->first());
            }
            });
        date_default_timezone_set('Asia/Jakarta');
        DetailSparepartPenjualan::observe(SparepartSaleObserver::class);
        DetailPartServices::observe(PartServiceObserver::class);
        PengeluaranOperasional::observe(PengeluaranOperasionalObserver::class);


        Blade::directive('lateFormat', function ($minutes) {
            return "<?php
                \$mins = $minutes;
                if (\$mins <= 0) {
                    echo 'Tepat waktu';
                } elseif (\$mins < 60) {
                    echo \$mins . ' menit';
                } else {
                    \$hours = floor(\$mins / 60);
                    \$remainingMins = \$mins % 60;
                    echo \$remainingMins == 0 ? \$hours . ' jam' : \$hours . ' jam ' . \$remainingMins . ' menit';
                }
            ?>";
        });
        Paginator::useBootstrapFour();

    }
}
