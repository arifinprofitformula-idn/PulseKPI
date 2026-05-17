<?php

namespace App\Filament\Resources\KpiAssessments\Schemas;

use App\Models\KpiAssessment;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class KpiAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $isReadOnly = fn (?KpiAssessment $record): bool => $record instanceof KpiAssessment
            ? ! (auth()->user()?->can('update', $record) ?? false)
            : false;

        return $schema
            ->components([
                Section::make('Assessment Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('employee_name')
                                    ->label('Employee')
                                    ->disabled(),
                                TextInput::make('assessor_name')
                                    ->label('Assessor')
                                    ->disabled(),
                                TextInput::make('period_name')
                                    ->label('KPI Period')
                                    ->disabled(),
                                TextInput::make('template_name')
                                    ->label('KPI Template')
                                    ->disabled(),
                                TextInput::make('status_label')
                                    ->label('Status')
                                    ->disabled(),
                                TextInput::make('grade')
                                    ->label('Grade')
                                    ->disabled(),
                                TextInput::make('kpi_score')
                                    ->label('KPI Score')
                                    ->disabled(),
                                TextInput::make('attendance_deduction')
                                    ->label('Attendance Deduction')
                                    ->disabled(),
                                TextInput::make('attendance_score')
                                    ->label('Attendance Score')
                                    ->disabled(),
                                TextInput::make('final_score')
                                    ->label('Final Score')
                                    ->disabled(),
                                TextInput::make('submitted_at_label')
                                    ->label('Submitted At')
                                    ->disabled()
                                    ->placeholder('-'),
                                TextInput::make('reviewed_at_label')
                                    ->label('Reviewed At')
                                    ->disabled()
                                    ->placeholder('-'),
                                TextInput::make('approved_at_label')
                                    ->label('Approved At')
                                    ->disabled()
                                    ->placeholder('-'),
                                TextInput::make('rejected_at_label')
                                    ->label('Rejected At')
                                    ->disabled()
                                    ->placeholder('-'),
                                TextInput::make('locked_at_label')
                                    ->label('Locked At')
                                    ->disabled()
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('KPI Components')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->schema([
                                Hidden::make('item_id'),
                                Hidden::make('template_item_id'),
                                Hidden::make('current_evidence_url'),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('item_name')
                                            ->label('KPI Component')
                                            ->disabled()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->disabled()
                                            ->rows(2),
                                        Textarea::make('target_description')
                                            ->label('Target')
                                            ->disabled()
                                            ->rows(2),
                                        TextInput::make('data_source')
                                            ->label('Data Source')
                                            ->disabled(),
                                        TextInput::make('weight')
                                            ->label('Weight')
                                            ->disabled(),
                                        TextInput::make('actual_value')
                                            ->label('Actual Value')
                                            ->numeric()
                                            ->step('0.01')
                                            ->disabled($isReadOnly),
                                        Select::make('score')
                                            ->label('Score')
                                            ->placeholder('Not assessed yet')
                                            ->options([
                                                0 => '0',
                                                1 => '1',
                                                2 => '2',
                                            ])
                                            ->required()
                                            ->disabled($isReadOnly),
                                        TextInput::make('weighted_score')
                                            ->label('Weighted Score')
                                            ->disabled(),
                                        Textarea::make('evidence_note')
                                            ->label('Evidence Note')
                                            ->rows(2)
                                            ->disabled($isReadOnly)
                                            ->columnSpanFull(),
                                        TextInput::make('current_evidence_name')
                                            ->label('Current Evidence')
                                            ->disabled()
                                            ->placeholder('No evidence uploaded')
                                            ->columnSpanFull(),
                                        Placeholder::make('evidence_download')
                                            ->label('Evidence Action')
                                            ->content(fn ($get): HtmlString => filled($get('current_evidence_url'))
                                                ? new HtmlString(sprintf(
                                                    '<a href="%s" class="text-primary-600 underline" target="_blank" rel="noopener noreferrer">Download evidence</a>',
                                                    e((string) $get('current_evidence_url'))
                                                ))
                                                : new HtmlString('<span class="text-gray-500">No downloadable evidence</span>'))
                                            ->columnSpanFull(),
                                        FileUpload::make('evidence_file')
                                            ->label('Upload Evidence')
                                            ->disk(config('pulsekpi.assessments.evidence_disk', 'local'))
                                            ->visibility('private')
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'image/jpeg',
                                                'image/png',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                            ])
                                            ->maxSize((int) config('pulsekpi.assessments.max_evidence_size_kb', 5120))
                                            ->storeFiles(false)
                                            ->previewable(false)
                                            ->downloadable(false)
                                            ->openable(false)
                                            ->getUploadedFileNameForStorageUsing(
                                                fn (TemporaryUploadedFile $file): string => $file->hashName()
                                            )
                                            ->disabled($isReadOnly)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
                Section::make('Attendance Adjustment')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('attendance.working_days')
                                    ->label('Working Days')
                                    ->numeric()
                                    ->required()
                                    ->disabled($isReadOnly),
                                TextInput::make('attendance.sick_days')
                                    ->label('Sick Days')
                                    ->numeric()
                                    ->required()
                                    ->disabled($isReadOnly),
                                TextInput::make('attendance.permission_days')
                                    ->label('Permission Days')
                                    ->numeric()
                                    ->required()
                                    ->disabled($isReadOnly),
                                TextInput::make('attendance.absent_days')
                                    ->label('Absent Days')
                                    ->numeric()
                                    ->required()
                                    ->disabled($isReadOnly),
                                TextInput::make('attendance.leave_days')
                                    ->label('Leave Days')
                                    ->numeric()
                                    ->required()
                                    ->disabled($isReadOnly),
                                TextInput::make('attendance.deduction_score')
                                    ->label('Deduction Score')
                                    ->disabled(),
                                TextInput::make('attendance.attendance_score')
                                    ->label('Attendance Score')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Assessment Notes')
                            ->rows(3)
                            ->disabled($isReadOnly),
                    ]),
                Section::make('Approval History')
                    ->schema([
                        Repeater::make('approval_history')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('action')
                                            ->label('Action')
                                            ->disabled(),
                                        TextInput::make('actor_name')
                                            ->label('Actor')
                                            ->disabled(),
                                        TextInput::make('from_status')
                                            ->label('From Status')
                                            ->disabled(),
                                        TextInput::make('to_status')
                                            ->label('To Status')
                                            ->disabled(),
                                        TextInput::make('acted_at')
                                            ->label('Acted At')
                                            ->disabled(),
                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->rows(2)
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
