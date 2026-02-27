<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class ProjectTemplateService
{
    /**
     * Get all active templates
     * Returns facts only (template data)
     */
    public function getAllActiveTemplates(): Collection
    {
        return DB::table('project_templates')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all templates (including inactive)
     * Admin only
     */
    public function getAllTemplates(): Collection
    {
        return DB::table('project_templates')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get templates by category
     * Returns facts only
     */
    public function getTemplatesByCategory(string $category): Collection
    {
        return DB::table('project_templates')
            ->where('category', $category)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get template with all tasks
     * Returns complete template specification
     */
    public function getTemplateWithTasks(int $templateId): ?array
    {
        $template = DB::table('project_templates')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            return null;
        }

        $tasks = DB::table('project_template_tasks')
            ->where('project_template_id', $templateId)
            ->orderBy('phase_number')
            ->get()
            ->toArray();

        return [
            'template' => (array)$template,
            'tasks' => $tasks,
        ];
    }

    /**
     * Validate that all template tasks sum to 100%
     * Returns true if valid, throws exception if not
     */
    public function validateTemplateWeights(int $templateId): bool
    {
        $totalWeight = DB::table('project_template_tasks')
            ->where('project_template_id', $templateId)
            ->sum('weight');

        if ($totalWeight !== 100) {
            throw new Exception("Template weight validation failed. Total weight is {$totalWeight}%, must be exactly 100%.");
        }

        return true;
    }

    /**
     * Get template by ID
     */
    public function getTemplate(int $templateId): ?object
    {
        return DB::table('project_templates')
            ->where('id', $templateId)
            ->first();
    }

    /**
     * Get template tasks
     */
    public function getTemplateTasks(int $templateId): Collection
    {
        return DB::table('project_template_tasks')
            ->where('project_template_id', $templateId)
            ->orderBy('phase_number')
            ->get();
    }

    /**
     * Create new template (Admin only)
     */
    public function createTemplate(array $data): int
    {
        return DB::table('project_templates')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'is_active' => $data['is_active'] ?? true,
            'average_duration_days' => $data['average_duration_days'] ?? null,
            'task_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update template (Admin only)
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        return DB::table('project_templates')
            ->where('id', $templateId)
            ->update([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'average_duration_days' => $data['average_duration_days'] ?? null,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Delete template (Admin only)
     * Only allow deletion if no projects use this template
     */
    public function deleteTemplate(int $templateId): bool
    {
        // Check if any projects use this template
        $projectCount = DB::table('opportunities')
            ->where('suggested_template_id', $templateId)
            ->count();

        if ($projectCount > 0) {
            throw new Exception("Cannot delete template. {$projectCount} projects are using this template.");
        }

        return DB::table('project_templates')
            ->where('id', $templateId)
            ->delete() > 0;
    }

    /**
     * Add task to template (Admin only)
     */
    public function addTaskToTemplate(int $templateId, array $taskData): int
    {
        return DB::table('project_template_tasks')->insertGetId([
            'project_template_id' => $templateId,
            'name' => $taskData['name'],
            'description' => $taskData['description'] ?? null,
            'weight' => $taskData['weight'],
            'phase_number' => $taskData['phase_number'],
            'estimated_duration_days' => $taskData['estimated_duration_days'] ?? null,
            'dependencies' => $taskData['dependencies'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update template task (Admin only)
     */
    public function updateTemplateTask(int $taskId, array $data): bool
    {
        return DB::table('project_template_tasks')
            ->where('id', $taskId)
            ->update([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'weight' => $data['weight'] ?? null,
                'phase_number' => $data['phase_number'] ?? null,
                'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
                'dependencies' => $data['dependencies'] ?? null,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Delete template task (Admin only)
     */
    public function deleteTemplateTask(int $taskId): bool
    {
        return DB::table('project_template_tasks')
            ->where('id', $taskId)
            ->delete() > 0;
    }

    /**
     * Get template categories
     */
    public function getCategories(): array
    {
        return DB::table('project_templates')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Check if template exists
     */
    public function templateExists(int $templateId): bool
    {
        return DB::table('project_templates')
            ->where('id', $templateId)
            ->exists();
    }

    /**
     * Get template name by ID
     */
    public function getTemplateName(int $templateId): ?string
    {
        return DB::table('project_templates')
            ->where('id', $templateId)
            ->value('name');
    }
}
