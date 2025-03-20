<?php
namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProfessorAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Your Professor Account has been Created')
                    ->view('emails.professor_account_created')
                    ->with([
                        'name' => $this->user->first_name . ' ' . $this->user->last_name,
                        'email' => $this->user->email,
                        'password' => $this->password,
                        'login_url' => url('/login'),
                    ]);
    }
}
