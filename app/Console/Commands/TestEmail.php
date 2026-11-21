<?php

namespace App\Console\Commands;

use App\Http\Middleware\Localization;
use App\Mail\AccountCreated;
use App\Mail\AccountDeleted;
use App\Mail\SiteCreated;
use App\Models\Site;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class TestEmail extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test email.';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string,string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'email' => 'Please enter the recipient email address for the test email(s):',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // List of mailable classes (in app/Mail directory)
        $mailableFiles = glob(app_path('Mail/*'));
        if ($mailableFiles === false || count($mailableFiles) === 0) {
            $this->fail('Failed to retrieve Mailable files.'); // @codeCoverageIgnore
        }
        $mailableClasses = collect($mailableFiles)
            ->map(fn (string $file) => basename($file, '.php'))
            ->map(fn (string $class) => app()->getNamespace().'Mail\\'.$class)
            ->filter(fn (string $class) => class_exists($class))
            ->values()
            ->toArray();

        $classes = multiselect('Which email do you want to send?', $mailableClasses);
        $locale = select('Which locale do you want to use?', Localization::LOCALES);

        foreach ($classes as $class) {
            /** @var Mailable $mailable */
            $mailable = match ($class) {
                SiteCreated::class => new $class(site: Site::inRandomOrder()->first()),
                AccountCreated::class,
                AccountDeleted::class => new $class(user: User::inRandomOrder()->first()),
                default => new $class, // @codeCoverageIgnore
            };

            Mail::to($this->argument('email'))
                ->locale((string) $locale)
                ->send($mailable);
        }

        $this->info('Test email(s) sent successfully.');
    }
}
