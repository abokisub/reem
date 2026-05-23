<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;

class SetCompanyNin extends Command
{
    protected $signature = 'company:set-nin {companyId} {nin}';
    protected $description = 'Manually assign a specific NIN to a company as backup_director_3_nin';

    public function handle()
    {
        $companyId = $this->argument('companyId');
        $nin = $this->argument('nin');

        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company not found.");
            return 1;
        }

        $company->backup_director_3_nin = $nin;
        $company->save();

        $this->info("Successfully assigned NIN {$nin} to company {$company->name} (backup_director_3_nin).");
        return 0;
    }
}
