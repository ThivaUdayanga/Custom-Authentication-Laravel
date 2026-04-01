<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\EmployeeRosterAssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LeaveCategoryController;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
| Login uses session, so it must have "web" middleware
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Auth Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Branch Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function(){
    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:Admin,HR Manager,Branch Manager'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Shift Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:Admin,HR Manager,Branch Manager'])->group(function () {
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/shifts/{shift}', [ShiftController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin,HR Manager'])->group(function () {
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Roster Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/rosters', [RosterController::class, 'index']);
    Route::get('/rosters/{roster}', [RosterController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin,HR Manager'])->group(function () {
    Route::post('/rosters', [RosterController::class, 'store']);
    Route::put('/rosters/{roster}', [RosterController::class, 'update']);
    Route::delete('/rosters/{roster}', [RosterController::class, 'destroy']);
});

//to be implement

/*
|--------------------------------------------------------------------------
| Employee Roster Assignment Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/employee-roster-assignments', [EmployeeRosterAssignmentController::class, 'index']);
    Route::get('/employee-roster-assignments/{employeeRosterAssignment}', [EmployeeRosterAssignmentController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin,HR Manager'])->group(function () {
    Route::post('/employee-roster-assignments', [EmployeeRosterAssignmentController::class, 'store']);
    Route::put('/employee-roster-assignments/{employeeRosterAssignment}', [EmployeeRosterAssignmentController::class, 'update']);
    Route::delete('/employee-roster-assignments/{employeeRosterAssignment}', [EmployeeRosterAssignmentController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Mark Attendance Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum', 'role:Admin,HR Manager,Branch Manager')->group(function () {
    Route::get('/getAttendanceRecords', [AttendanceController::class, 'getAttendanceRecords']);
});

Route::middleware(['auth:sanctum', 'role:Branch Manager,HR Manager, Employee'])->group(function () {
    Route::post('/mark-attendance', [AttendanceController::class, 'markAttendance']);
});

/*
|--------------------------------------------------------------------------
| Leave Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::get('/leaves/{leave}', [LeaveController::class, 'show']);
    Route::get('/users/{userId}/leaves', [LeaveController::class, 'getEmployeeLeaves']);
});

Route::middleware(['auth:sanctum', 'role:Employee,HR Manager,Branch Manager'])->group(function () {
    Route::post('/leaves', [LeaveController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:Admin,HR Manager,Branch Manager'])->group(function () {
    Route::patch('/leaves/{leave}/status', [LeaveController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'role:Employee,HR Manager'])->group(function () {
    Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Leave Category Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leave-categories', [LeaveCategoryController::class, 'index']);
    Route::get('/leave-categories/applicable', [LeaveCategoryController::class, 'getApplicableCategories']);
    Route::get('/leave-categories/{leaveCategory}', [LeaveCategoryController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin,Branch Manager'])->group(function () {
    Route::post('/leave-categories', [LeaveCategoryController::class, 'store']);
    Route::put('/leave-categories/{leaveCategory}', [LeaveCategoryController::class, 'update']);
    Route::delete('/leave-categories/{leaveCategory}', [LeaveCategoryController::class, 'destroy']);
});