<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowStep;

class WorkflowTemplateService
{
    public static function getTemplates(): array
    {
        return [
            'post_showing_followup' => [
                'name' => __('Post-Showing Follow-up'),
                'description' => __('Automatic follow-up after a showing/meeting activity'),
                'trigger_type' => 'manual',
                'steps' => [
                    ['type' => 'delay', 'config' => ['delay_minutes' => 60], 'position' => 1],
                    ['type' => 'action', 'config' => ['action' => 'send_email', 'subject' => 'Thank you for your time', 'body' => 'Thank you for meeting with us. We appreciate the opportunity to discuss your property.'], 'position' => 2],
                    ['type' => 'delay', 'config' => ['delay_minutes' => 4320], 'position' => 3],
                    ['type' => 'action', 'config' => ['action' => 'create_task', 'title' => 'Follow up after showing'], 'position' => 4],
                ],
            ],
            'post_open_house' => [
                'name' => __('Post-Open House Follow-up'),
                'description' => __('Follow-up sequence for open house attendees'),
                'trigger_type' => 'manual',
                'steps' => [
                    ['type' => 'action', 'config' => ['action' => 'send_email', 'subject' => 'Thank you for visiting our open house', 'body' => 'It was great to meet you at our open house. We hope you enjoyed the tour.'], 'position' => 1],
                    ['type' => 'delay', 'config' => ['delay_minutes' => 2880], 'position' => 2],
                    ['type' => 'action', 'config' => ['action' => 'create_task', 'title' => 'Follow up with open house attendee'], 'position' => 3],
                ],
            ],
            'offer_followup' => [
                'name' => __('Offer Follow-up'),
                'description' => __('Follow-up after offer is received on a deal'),
                'trigger_type' => 'deal_stage_change',
                'trigger_config' => ['stage' => 'offer_received'],
                'steps' => [
                    ['type' => 'delay', 'config' => ['delay_minutes' => 1440], 'position' => 1],
                    ['type' => 'action', 'config' => ['action' => 'create_task', 'title' => 'Review and respond to offer'], 'position' => 2],
                ],
            ],
            'price_drop_alert' => [
                'name' => __('Price Drop Alert'),
                'description' => __('Notify matched buyers when price drops'),
                'trigger_type' => 'manual',
                'steps' => [
                    ['type' => 'action', 'config' => ['action' => 'send_email', 'subject' => 'Price Update on a Property You May Like', 'body' => 'We wanted to let you know about a price update on a property that matches your criteria.'], 'position' => 1],
                ],
            ],
            'post_consultation' => [
                'name' => __('Post-Consultation Sequence'),
                'description' => __('Nurture sequence after initial consultation'),
                'trigger_type' => 'manual',
                'steps' => [
                    ['type' => 'delay', 'config' => ['delay_minutes' => 60], 'position' => 1],
                    ['type' => 'action', 'config' => ['action' => 'send_email', 'subject' => 'Great meeting you today', 'body' => 'Thank you for taking the time to meet with us. Here is a summary of what we discussed.'], 'position' => 2],
                    ['type' => 'delay', 'config' => ['delay_minutes' => 10080], 'position' => 3],
                    ['type' => 'action', 'config' => ['action' => 'send_email', 'subject' => 'Checking in', 'body' => 'Just wanted to check in and see if you have any questions about our discussion.'], 'position' => 4],
                    ['type' => 'delay', 'config' => ['delay_minutes' => 20160], 'position' => 5],
                    ['type' => 'action', 'config' => ['action' => 'create_task', 'title' => 'Final follow-up after consultation'], 'position' => 6],
                ],
            ],
        ];
    }

    public static function seedForTenant(Tenant $tenant): void
    {
        foreach (self::getTemplates() as $key => $template) {
            $workflow = Workflow::create([
                'tenant_id' => $tenant->id,
                'name' => $template['name'],
                'description' => $template['description'],
                'trigger_type' => $template['trigger_type'],
                'trigger_config' => $template['trigger_config'] ?? null,
                'is_active' => false,
            ]);

            foreach ($template['steps'] as $step) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'type' => $step['type'],
                    'config' => $step['config'],
                    'position' => $step['position'],
                ]);
            }
        }
    }
}
