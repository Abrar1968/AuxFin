<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Liability;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MessageRead;
use App\Models\Project;
use App\Models\PublicHoliday;
use App\Models\SalaryMonth;
use App\Models\Setting;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = AuditObserver::class;

        User::observe($observer);
        Employee::observe($observer);
        SalaryMonth::observe($observer);
        Loan::observe($observer);
        LoanRepayment::observe($observer);
        Leave::observe($observer);
        Attendance::observe($observer);
        Client::observe($observer);
        Project::observe($observer);
        Invoice::observe($observer);
        Expense::observe($observer);
        Liability::observe($observer);
        Asset::observe($observer);
        EmployeeMessage::observe($observer);
        MessageRead::observe($observer);
        PublicHoliday::observe($observer);
        Setting::observe($observer);
    }
}
