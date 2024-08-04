<?php

namespace AhmedEbead\LaraMultiAuth\Mails;

use Illuminate\Mail\Mailable;



class WelcomeEmail extends Mailable

{

    public function build()

    {

        return $this->subject('Welcome to Our Website')

            ->view('laramultiauth::email.welcomeEmail')

            ->with([

                'username' => $this->user->name,

            ]);

    }

}
