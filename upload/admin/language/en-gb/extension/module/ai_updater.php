<?php
// Heading
$_['heading_title']    = 'AI Ürün Güncelleme'; // Updated Name

// Menu Text (added for OCMOD menu integration)
$_['text_ai_updater_menu'] = 'AI Ürün Güncelleme';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Success: You have modified AI Product Updater module settings!';
$_['text_edit']        = 'Edit AI Product Updater Module';
$_['text_enabled']     = 'Enabled';
$_['text_disabled']    = 'Disabled';
$_['text_field_name']  = 'Product Name';
$_['text_field_description'] = 'Product Description';
$_['text_field_meta_title'] = 'Meta Tag Title';
$_['text_field_meta_description'] = 'Meta Tag Description';
$_['text_field_meta_keyword'] = 'Meta Tag Keywords';
$_['text_field_seo_url'] = 'SEO URL';
$_['text_no_products_found'] = 'No products found. Please refine your search.';
$_['text_products_updated_success'] = 'Success: %s products have been sent for AI update!'; // %s will be replaced by the number of products
$_['text_no_products_updated'] = 'Warning: No products were updated. This might be due to API errors or no new data generated.';
$_['text_product_updated_success_single'] = 'Product ID %s: Content successfully generated and updated.'; // For single product success in progress
$_['text_processing_product'] = 'Processing product';
$_['text_sending_data_to_ai'] = 'Sending data to AI...';
$_['text_sending_data_to_ai_short']    = 'Sending to AI...'; // Shorter version for live log
$_['text_update_complete'] = 'Update process complete.';
$_['text_complete'] = 'Complete';
$_['text_error_occurred'] = 'An error occurred.';
$_['text_completed_with_errors_summary'] = 'Process completed with errors:';
$_['text_completed_with_warnings_summary'] = 'Process completed with warnings:';
$_['text_all_products_updated_successfully'] = 'All products processed successfully!';
$_['text_products_updated_successfully_summary'] = 'products processed successfully.'; // Used like "X products processed successfully"


// Tab
$_['tab_product_update'] = 'Product Update Tool';

// Entry
$_['entry_status']     = 'Status';
$_['entry_api_key']    = 'Openrouter API Key';
$_['entry_fields_to_update'] = 'Fields to Update';
$_['entry_manual_description'] = 'Manual Instructions for AI';
$_['entry_products']   = 'Select Products';
$_['entry_filter_name'] = 'Filter by Product Name';

// Help
$_['help_api_key']     = 'Enter your Openrouter.ai API key. This is required to generate content.';
$_['help_fields_to_update'] = 'Select which product fields you want the AI to update.';
$_['help_manual_description'] = 'Optionally, provide specific instructions or context for the AI to consider when updating the selected fields (e.g., "Focus on benefits for young professionals", "Use a casual tone").';

// Button
$_['button_update_products'] = 'Update Selected Products with AI';
$_['button_save_settings'] = 'Save Settings';
// button_cancel is a global lang var in OpenCart

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify the AI Product Updater module!';
$_['error_api_key_missing']    = 'Error: Openrouter API Key is missing in settings. Please add it and save.';
$_['error_api_key_required'] = 'Warning: Openrouter API Key is required!'; // For settings validation
$_['error_missing_data'] = 'Error: Missing product selection or fields to update.';
$_['error_no_product_selected'] = 'Error: Please select at least one product to update.';
$_['error_no_fields_selected'] = 'Error: Please select at least one field to update.';
$_['error_invalid_request'] = 'Error: Invalid request method.';

// Log Messages (Not directly in language file, but for reference)
// Log messages are usually in English in the code for developers.
// 'AI Updater: Database backup created.'
// 'AI Updater: Product ID X updated. Data: {json}'
// 'AI Updater: Error processing product ID X. API Error: {message}'
// 'AI Updater (Dummy): Processing product X (ID: Y)'
// 'AI Updater (Dummy): Fields to update: a, b, c'
// 'AI Updater (Dummy): Manual description: ...'
// 'AI Updater (Dummy): Generated data: {json}'
?>
