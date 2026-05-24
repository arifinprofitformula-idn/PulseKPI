<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\User;
use App\Services\Dashboard\SupervisorDashboardService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class SupervisorDashboard extends Page
{
    protected static ?string $slug = 'supervisor-dashboard';

    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::SUPERVISOR->value) || $user->hasRole(SystemRole::SUPER_ADMIN->value));
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(SystemRole::SUPERVISOR->value);
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard Supervisor';
    }

    public function getTitle(): string
    {
        return 'Dashboard Supervisor';
    }

    public function getHeading(): string
    {
        return 'Dashboard Supervisor';
    }

    public function getSubheading(): ?string
    {
        return 'Selamat datang, Supervisor. Pantau KPI staff Anda dan selesaikan assessment yang membutuhkan tindakan.';
    }

    public function content(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = auth()->user();
        $data = $user instanceof User
            ? app(SupervisorDashboardService::class)->getDashboardData($user)
            : ['metrics' => [], 'pendingQueue' => [], 'recentAssessments' => []];

        return $schema->components([
            Grid::make([
                'default' => 1,
                'xl' => 3,
            ])->schema(array_map(
                fn (array $metric): Section => Section::make($metric['label'])
                    ->schema([
                        Html::make(new HtmlString(sprintf(
                            '<div class="space-y-2"><p class="text-3xl font-semibold text-gray-950 dark:text-white">%s</p><p class="text-sm text-gray-600 dark:text-gray-300">%s</p></div>',
                            e($metric['value']),
                            e($metric['description']),
                        ))),
                    ]),
                $data['metrics'],
            )),
            Section::make('Pending Assessment Queue')
                ->description('Daftar assessment staff langsung yang masih membutuhkan perhatian Anda.')
                ->schema([
                    empty($data['pendingQueue'])
                        ? EmptyState::make('No assignments are waiting on you right now.')
                            ->description('Draft, rejected, dan assessment terbaru akan muncul di sini saat workflow berjalan.')
                            ->icon('heroicon-o-inbox')
                        : Html::make($this->renderTable(
                            ['Employee', 'Period', 'Template', 'Status'],
                            array_map(fn (array $row): array => [
                                $row['employee'],
                                $row['period'],
                                $row['template'],
                                $row['status'],
                            ], $data['pendingQueue']),
                        )),
                ]),
            Section::make('Recent Staff Assessments')
                ->description('Ringkasan assessment terbaru untuk staff langsung Anda.')
                ->schema([
                    empty($data['recentAssessments'])
                        ? EmptyState::make('No team members in your dashboard scope yet.')
                            ->description('Once employees report to you and receive KPI assignments, their latest status will appear here.')
                            ->icon('heroicon-o-user-group')
                        : Html::make($this->renderTable(
                            ['Employee', 'Period', 'Status', 'Score', 'Updated'],
                            array_map(fn (array $row): array => [
                                $row['employee'],
                                $row['period'],
                                $row['status'],
                                $row['score'],
                                $row['updated_at'],
                            ], $data['recentAssessments']),
                        )),
                ]),
        ]);
    }

    /**
     * @return list<array{label: string, url: string, style: string}>
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (KpiAssignmentResource::canViewAny()) {
            $actions[] = Action::make('openTeamKpi')
                ->label('Open Team KPI')
                ->url(KpiAssignmentResource::getUrl('index'))
                ->color('gray');
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = Action::make('openAssessmentQueue')
                ->label('Open Assessment Queue')
                ->url(KpiAssessmentResource::getUrl('index'));
        }

        return $actions;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function renderTable(array $headers, array $rows): HtmlString
    {
        $thead = collect($headers)
            ->map(fn (string $header): string => sprintf(
                '<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">%s</th>',
                e($header),
            ))
            ->implode('');

        $tbody = collect($rows)
            ->map(fn (array $row): string => sprintf(
                '<tr class="border-t border-gray-200 dark:border-gray-800">%s</tr>',
                collect($row)
                    ->map(fn (string $cell): string => sprintf(
                        '<td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">%s</td>',
                        e($cell),
                    ))
                    ->implode(''),
            ))
            ->implode('');

        return new HtmlString(sprintf(
            '<div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800"><table class="w-full divide-y divide-gray-200 dark:divide-gray-800"><thead class="bg-gray-50 dark:bg-gray-900/40"><tr>%s</tr></thead><tbody class="bg-white dark:bg-gray-950">%s</tbody></table></div>',
            $thead,
            $tbody,
        ));
    }
}
