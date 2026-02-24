<?php

return [
    // Common
    'title' => 'Title',
    'slug' => 'Slug',
    'is_active' => 'Active',
    'sorting' => 'Sorting',
    'created_at' => 'Created at',
    'category' => 'Category',

    // Actions
    'save' => 'Save',
    'create' => 'Create',
    'back' => 'Back',
    'close' => 'Close',
    'done' => 'Done',
    'delete' => 'Delete',
    'delete_confirm' => 'Are you sure you want to delete?',
    'editing' => 'Editing',
    'creating' => 'Creating',

    // Toast messages
    'item_created' => 'Item created',
    'item_saved' => 'Changes saved',
    'item_deleted' => 'Item deleted',
    'save_error' => 'Save error',

    // Admin settings menu
    'menu' => [
        'blocks' => 'Blocks',
        'groups' => 'Block groups',
    ],

    // Pages
    'block_item' => 'Block item',
    'block_items' => 'Block items',
    'block_not_found' => 'Block not found',
    'categories' => 'Categories',
    'items_list' => 'Items',

    // Tabs
    'main' => 'Main',
    'fields_tab' => 'Fields',
    'content' => 'Content',

    // Fields (table columns)
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'is_active' => 'Active',
        'sorting' => 'Sorting',
    ],

    // Export
    'export' => [
        'button' => 'Export',
        'title' => 'Export blocks',
        'include_groups' => 'Export with groups',
        'include_groups_hint' => 'If enabled, block groups will also be exported',
        'hint' => 'Select blocks in the table and click "Generate". Copy the generated code for import.',
        'generate' => 'Generate',
        'result' => 'Result',
        'select_blocks' => 'Select blocks to export',
        'success' => 'Export completed successfully',
        'error' => 'Export error',
        'copied' => 'Copied to clipboard',
    ],

    // Import
    'import' => [
        'button' => 'Import',
        'title' => 'Import blocks',
        'data_label' => 'Import data',
        'placeholder' => 'Paste the exported string here...',
        'hint' => 'Paste the export code and click "Import". Existing blocks with the same slugs will be updated.',
        'enter_data' => 'Enter import data',
        'error' => 'Import error',
        'success' => 'Imported: :groups groups, :blocks blocks',
        'partial_success' => 'Imported: :groups groups, :blocks blocks. Errors: :errors',
    ],

    // Block form
    'block' => [
        'tab_main' => 'Main',
        'tab_fields' => 'Block fields',
        'name' => 'Name',
        'slug' => 'Slug',
        'group' => 'Group',
        'is_multiple' => 'Multiple block',
        'is_multiple_hint' => 'This setting controls the ability to create items inside the block',
        'is_active' => 'Active',
        'sorting' => 'Sorting',
        'fields' => 'Block fields',
    ],

    // Fieldset field type
    'fieldset' => [
        'label'                => 'Fieldset',
        'select_label'         => 'Fieldset',
        'select_placeholder'   => '— Select fieldset —',
        'no_fieldsets'         => 'No fieldsets available.',
        'defaults_label'       => 'Default values',
        'no_defaults_available' => 'This fieldset has no fields that support default values.',
    ],

    // Block relation field type
    'block_relation' => [
        'label' => 'Block relation',
        'title' => 'Relation settings',
        'relation_type' => 'Relation type',
        'type_block' => 'Link to block',
        'type_group' => 'Link to group',
        'relation_type_hint' => 'Block — select items from a multiple block. Group — select blocks from a group.',
        'select_block' => 'Select block',
        'select_group' => 'Select group',
        'select_placeholder' => '— Select —',
        'select_block_hint' => 'Items from this block will be available for selection.',
        'select_group_hint' => 'Blocks from this group will be available for selection.',
        'multiple' => 'Multiple selection',
        'multiple_hint' => 'Allows selecting multiple items.',
        'no_blocks' => 'No multiple blocks available.',
        'no_groups' => 'No groups available.',
    ],

];
