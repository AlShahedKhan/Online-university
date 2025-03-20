<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Application;

class ApplicationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $password;
    public $batch_id;
    public $student_id;


    /**
     * Create a new message instance.
     */
    public function __construct(Application $application, $password, $batch_id, $student_id )
    {
        $this->application = $application;
        $this->password = $password;
        $this->batch_id = $batch_id;
        $this->student_id = $student_id;
    }

    public function build()
    {
        return $this->subject('Your Application has been Approved - Login Details')
            ->view('emails.application_approved')
            ->with([
                'application' => $this->application,
                'password' => $this->password,  // Now using the passed password
                'batch_id' => $this->batch_id,
                'student_id' => $this->student_id
            ]);
    }
}
