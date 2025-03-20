<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MCQController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\OurFacultyController;
use App\Http\Controllers\TuitionFeeController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BatchCourseController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\StudentMarksController;
use App\Http\Controllers\OurDepartmentController;
use App\Http\Controllers\VideoProgressController;
use App\Http\Controllers\AdministrationController;

// User routes
Route::post('/login', [UserController::class, 'login']);
Route::post('forgot-password', [UserController::class, 'forgotPassword']);
Route::post('verify-otp', [UserController::class, 'verifyOtp']);
Route::post('reset-password', [UserController::class, 'resetPassword']);

Route::post('/contact', [MessageController::class, 'store']);

// Application routes
Route::post('/applications', [ApplicationController::class, 'store']);

Route::get('/our-faculty', [OurFacultyController::class, 'index']);
Route::get('/our-faculty/{id}/our-department', [OurDepartmentController::class, 'index']);
Route::get('/our-faculty/{facultyId}/our-department/{departmentId}/eligibility', [EligibilityController::class, 'index']);
Route::get('/our-faculty/{facultyId}/our-department/{departmentId}/tuition-fee', [TuitionFeeController::class, 'index']);
Route::get('/our-faculty/{facultyId}/our-department/{departmentId}/eligibility', [EligibilityController::class, 'index']);




Route::group(['middleware' => ['auth:api']], function () {

    Route::get('/users', [UserController::class, 'getProfile']);

    // Application routes
    Route::prefix('applications')->controller(ApplicationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{application}', 'show');
        Route::post('/{application}/approve-or-reject', 'approveOrReject');
    });

    Route::get('dashboard', [AdministrationController::class, 'index']);
    Route::get('student-analysis/{year}', [AdministrationController::class, 'getStudentAnalysis']);
    Route::get('course-overview', [AdministrationController::class, 'courseOverview']);
    // Batch routes
    Route::prefix('batches')->controller(BatchController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{batch}', 'show');
        Route::post('/{batch?}', 'storeOrUpdate');
        Route::delete('/{batch}',  'destroy');
    });

    // Course routes
    Route::prefix('courses')->controller(CourseController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/{course?}', 'storeOrUpdate');
        Route::delete('/{course}',  'destroy');
    });

    // Professor routes
    Route::prefix('professors')->controller(ProfessorController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/{id?}', 'storeOrUpdate');
        Route::get('/course', 'getProfessorCourseLists');
        Route::get('/{id}', 'getProfessorDetails');
        Route::get('/batch/{id}', 'getBatchStudentLists');
        Route::get('/get/batches', 'getBatchList');
        Route::get('/get/students-with-results', 'getBatchStudentsWithResults');
        Route::delete('/{professor}', 'destroy');
        Route::get('/get/batch', 'getBatch');
        Route::get('/get/batch/{id}/course', 'getCourse');
        Route::get('/batch/{batch_id}/course/{course_id}/materials',  'getMaterials');
    });

    Route::post('/assign-result/{student_id}', [ResultController::class, 'assignResult']);



    // Materials routes
    Route::prefix('materials')->controller(MaterialController::class)->group(function () {
        Route::get('/', 'index'); // Fetch all materials
        Route::get('/admin/{course}', 'fetchCoursesByCourseIdForAdmin'); // Fetch materials for a specific course for admin
        Route::get('/{course}', 'fetchCoursesByCourseId'); // Fetch materials for a specific course
        Route::post('/{id?}', 'storeOrUpdate');
        Route::delete('/{material}', 'destroy');

        Route::post('/assign-marks/course/{courseId}/material/{materialId}/student/{studentId}', 'assignAssignmentMarks');
        Route::get('/get-marks/course/{courseId}/student/{studentId}', 'getAssignmentMarks');

        Route::get('/get-total-marks/student/{studentId}', 'getTotalMarksByStudent');

        Route::get('/get-average-marks/student/{studentId}', 'getAverageMarksByStudent');

        Route::get('/get/total-marks-expected', 'getExpectedMarks');

        Route::post('/submit-assignment/{courseId}/{materialId}', 'submitAssignment');
        Route::get('/get-assignment/{courseId}/{materialId}', 'getAssignment');
        Route::get('/get-marks-of-assignment/{courseId}', 'getMarksOfAssignment');
        Route::get('/course/{courseId}/total-marks', 'getTotalMarksForCourse');
        Route::get('/show/{material}', 'show');
    });

    Route::prefix('students')->controller(StudentController::class)->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::get('/{student}', [StudentController::class, 'show']);
        Route::post('/update', [StudentController::class, 'update']);
        Route::get('/student/courses-with-materials', 'getStudentCoursesWithMaterialsAndProfessor');
        Route::get('/get/student/courses', 'getStudentCourses');
        Route::get('/get/courses-and-materials', 'getCoursesAndMaterials');
        Route::get('/get/results', 'getResults');
        Route::get('/get/courses', 'getCourses');
        Route::get('/get/materials/{id}', 'getMaterials');
    });

    Route::post('/batch-course/store-or-update', [BatchCourseController::class, 'storeOrUpdate']);
    Route::get('batch/{batchId}/courses', [BatchCourseController::class, 'getCoursesForBatch']);


    Route::get("logout", [UserController::class, "logout"]);

    Route::get('certificate/{student_id}', [StudentController::class, 'getCertificate']);


    Route::post('/our-faculty/store-or-update/{id?}', [OurFacultyController::class, 'storeOrUpdate']);
    Route::delete('/our-faculty/{faculty}', [OurFacultyController::class, 'destroy']);


    Route::post('/our-department/store-or-update/{id?}', [OurDepartmentController::class, 'storeOrUpdate']);
    Route::delete('/our-faculty/{facultyId}/our-department/{departmentId}', [OurDepartmentController::class, 'destroy']);

    Route::post('/tuition-fee/store-or-update/{id?}', [TuitionFeeController::class, 'storeOrUpdate']);
    Route::delete('/our-faculty/{facultyId}/our-department/{departmentId}/tuition-fee/{tuitionFeeId}', [TuitionFeeController::class, 'destroy']);

    Route::post('/eligibility/store-or-update/{id?}', [EligibilityController::class, 'storeOrUpdate']);
    Route::delete('/our-faculty/{facultyId}/our-department/{departmentId}/eligibility/{eligibilityId}', [EligibilityController::class, 'destroy']);

    Route::get('/contact', [MessageController::class, 'index']);

    Route::post('checkout', [PaymentController::class, 'checkout']);
    Route::get('payments/admin', [PaymentController::class, 'getAdminPayments']);
    Route::get('payments/student', [PaymentController::class, 'getStudentPayments']);
    Route::get('get-admission-fee', [PaymentController::class, 'getAdmissionFee']);


    Route::get('/course/{id}/videos', [VideoProgressController::class, 'getVideos']);
    Route::get('/course/{course_id}/material/{material_id}/video', [VideoProgressController::class, 'getVideoShow']);
    Route::post('/videos/progress', [VideoProgressController::class, 'updateProgress']);


    Route::get('/videos/progress/all', [VideoProgressController::class, 'trackProgress']);
    Route::get('student/{student_id}/videos/progress/all', [VideoProgressController::class, 'trackProgressForStudent']);



    Route::prefix('mcqs')->group(function () {
        Route::post('/store-or-update/{id?}', [MCQController::class, 'storeOrUpdate'])
            ->name('mcqs.storeOrUpdate');
        Route::get('/course/{course_id}/materials/{material_id}', [MCQController::class, 'index']);
        Route::delete('/course/{course_id}/materials/{material_id}/mcq/{mcq_id}', [MCQController::class, 'destroy']);

        Route::post('/course/{course_id}/materials/{material_id}/mcq/submit', [MCQController::class, 'submitAnswers']);
        Route::get('/course/{courseId}/materials/{materialId}/marks', [MCQController::class, 'getStudentMarksByMaterial']);
        Route::get('/student/overall-marks', [MCQController::class, 'getStudentOverallMarks']);


        Route::get('student/{student_id}/course/{courseId}/materials/{materialId}/marks', [MCQController::class, 'getStudentMarksByMaterialForAdmin']);
        Route::get('/student/{student_id}/overall-marks', [MCQController::class, 'getStudentOverallMarksForAdmin']);
    });

    Route::get('/student/course/{courseId}/marks', [StudentMarksController::class, 'getStudentMarks']);
    Route::get('/student/{student_id}/marks', [StudentMarksController::class, 'getStudentOvallMarksForAdmin']);
    Route::get('get/student/assignment_orverall/marks', [StudentMarksController::class, 'getStudentAssignmentOverallMarks']);
    Route::get('/student/{student_id}/course/{courseId}/marks', [StudentMarksController::class, 'getStudentMarksForAdmin']);

    Route::get('/calculate-cgpa/student/{student_id}', [StudentController::class, 'calculateCGPA']);

    Route::post('/create-certificate/{student_id}', [StudentController::class, 'createCertificate']);
    Route::get('/get-certificate', [StudentController::class, 'getCertificateForAdmin']);
    Route::post('/approve-certificate/{certificate_id}', [StudentController::class, 'approveCertificate']);
    Route::get('/get-certificate-for-student', [StudentController::class, 'getCertificateForStudent']);
});
