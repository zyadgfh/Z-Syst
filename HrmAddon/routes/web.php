<?php

use Illuminate\Support\Facades\Route;
use Modules\HrmAddon\App\Http\Controllers as HRM;

Route::group(['as' => 'hrm.', 'prefix' => 'hrm', 'middleware' => ['users', 'expired']], function () {

    Route::resource('department', HRM\AcnooDepartmentController::class);
    Route::post('department/filter', [HRM\AcnooDepartmentController::class, 'acnooFilter'])->name('department.filter');
    Route::post('department/status/{id}',[HRM\AcnooDepartmentController::class,'status'])->name('department.status');
    Route::post('department/delete-all', [HRM\AcnooDepartmentController::class,'deleteAll'])->name('department.delete-all');

    Route::resource('designations', HRM\AcnooDesignationController::class);
    Route::post('designations/filter', [HRM\AcnooDesignationController::class, 'acnooFilter'])->name('designations.filter');
    Route::post('designations/status/{id}',[HRM\AcnooDesignationController::class,'status'])->name('designations.status');
    Route::post('designations/delete-all', [HRM\AcnooDesignationController::class,'deleteAll'])->name('designations.delete-all');

    Route::resource('shifts', HRM\AcnooShiftController::class);
    Route::post('shifts/filter', [HRM\AcnooShiftController::class, 'acnooFilter'])->name('shifts.filter');
    Route::post('shifts/status/{id}',[HRM\AcnooShiftController::class,'status'])->name('shifts.status');
    Route::post('shifts/delete-all', [HRM\AcnooShiftController::class,'deleteAll'])->name('shifts.delete-all');

    Route::resource('employees', HRM\AcnooEmployeeController::class)->except('show');
    Route::post('employees/filter', [HRM\AcnooEmployeeController::class, 'acnooFilter'])->name('employees.filter');
    Route::post('employees/status/{id}',[HRM\AcnooEmployeeController::class,'status'])->name('employees.status');
    Route::post('employees/delete-all', [HRM\AcnooEmployeeController::class,'deleteAll'])->name('employees.delete-all');

    Route::resource('leave-types', HRM\AcnooLeaveTypeController::class)->except('show');
    Route::post('leave-types/status/{id}', [HRM\AcnooLeaveTypeController::class, 'status'])->name('leave-types.status');
    Route::post('leave-types/delete-all', [HRM\AcnooLeaveTypeController::class, 'deleteAll'])->name('leave-types.delete-all');
    Route::post('leave-types/filter', [HRM\AcnooLeaveTypeController::class, 'acnooFilter'])->name('leave-types.filter');

    Route::resource('leaves', HRM\AcnooLeaveController::class);
    Route::post('leaves/delete-all', [HRM\AcnooLeaveController::class, 'deleteAll'])->name('leaves.delete-all');
    Route::post('leave/filter', [HRM\AcnooLeaveController::class, 'acnooFilter'])->name('leaves.filter');
    Route::post('leave/status/{id}', [HRM\AcnooLeaveController::class, 'status'])->name('leaves.status');
    Route::get('leave/department', [HRM\AcnooLeaveController::class, 'getDepartment'])->name('leaves.get.department');


    Route::resource('holidays', HRM\AcnooHolidayController::class);
    Route::post('holidays/filter', [HRM\AcnooHolidayController::class, 'acnooFilter'])->name('holidays.filter');
    Route::post('holidays/delete-all', [HRM\AcnooHolidayController::class,'deleteAll'])->name('holidays.delete-all');

    Route::resource('attendances', HRM\AcnooAttendanceController::class)->except('show');
    Route::post('attendances/delete-all', [HRM\AcnooAttendanceController::class, 'deleteAll'])->name('attendances.delete-all');
    Route::post('attendances/filter', [HRM\AcnooAttendanceController::class, 'acnooFilter'])->name('attendances.filter');
    Route::get('attendances/shift', [HRM\AcnooAttendanceController::class, 'getShift'])->name('attendances.getShift');

    Route::resource('payrolls', HRM\AcnooPayrollController::class)->except('show');
    Route::post('payrolls/delete-all', [HRM\AcnooPayrollController::class, 'deleteAll'])->name('payrolls.delete-all');
    Route::post('payrolls/filter', [HRM\AcnooPayrollController::class, 'acnooFilter'])->name('payrolls.filter');
    Route::post('payrolls/status/{id}', [HRM\AcnooPayrollController::class, 'status'])->name('payrolls.status');
    Route::get('payrolls/amount', [HRM\AcnooPayrollController::class, 'getEmpAmount'])->name('payrolls.getEmpAmount');

    Route::resource('attendance-reports', HRM\AcnooAttendanceReport::class)->only('index');
    Route::post('attendance-reports/filter', [HRM\AcnooAttendanceReport::class, 'acnooFilter'])->name('attendance-reports.filter');

    Route::resource('payroll-reports', HRM\AcnooPayrollReportController::class)->only('index');
    Route::post('payroll-reports/filter', [HRM\AcnooPayrollReportController::class, 'acnooFilter'])->name('payroll-reports.filter');

    Route::resource('leave-reports', HRM\AcnooLeaveReportController::class)->only('index');
    Route::post('leave-reports/filter', [HRM\AcnooLeaveReportController::class, 'acnooFilter'])->name('leave-reports.filter');
});
