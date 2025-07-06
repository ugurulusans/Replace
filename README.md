# AI Product Updater for OpenCart 3.0.x

The AI Product Updater is an OpenCart extension designed to automatically update product information using AI-powered content generation via the Openrouter.ai API. This module helps store administrators enrich product listings by generating new content for fields like product name, description, meta title, meta description, and meta keywords.

## Features

*   **Admin Panel Integration:** Manage AI updates directly from the OpenCart admin interface.
*   **Selective Product Updates:** Choose specific products to update using checkboxes.
*   **Field Selection:** Select which product fields (name, description, meta title, meta description, meta keywords) to update with AI-generated content.
*   **Manual AI Instructions:** Provide custom instructions to the AI for more tailored content generation.
*   **Openrouter.ai API Integration:** Leverages various AI models available through Openrouter.
*   **Automated Database Backup:** Automatically creates a database backup (`dump.sql`) before starting the update process.
*   **Progress Tracking:** Real-time progress bar displays the overall status of multi-product updates.
*   **Live Log Output:** Displays individual status (success, error, warning) for each product in real-time during the update process.
*   **Detailed Logging:** Logs API interactions and update statuses for troubleshooting (check OpenCart's system error logs for entries prefixed with "AI Updater:").
*   **SEO URL Generation:** Automatically generates/updates SEO-friendly URLs when product names are changed by the AI.

## Requirements

*   OpenCart 3.0.x (Tested on 3.0.3.6)
*   An active Openrouter.ai API Key.
*   PHP cURL extension enabled on your server.

## Installation

1.  **Download:** Obtain the extension files. These will typically be in a compressed format (e.g., `.zip`).
2.  **Extract:** Extract the downloaded archive. You should see an `upload/` directory (or similar, containing `admin/`, `system/` etc.).
3.  **Upload Files:**
    *   Using an FTP client (like FileZilla) or your hosting control panel's File Manager, upload the contents of the `upload/` directory to the root directory of your OpenCart installation.
    *   Ensure that you merge the directories, do not overwrite your existing `admin` or `system` folders entirely, just the new files within them.
4.  **Permissions:**
    *   Go to **System > Users > User Groups** in your OpenCart admin panel.
    *   Select your user group (e.g., "Administrator") and click "Edit".
    *   Under "Access Permission" and "Modify Permission", find and check the box for `extension/module/ai_updater`.
    *   Click "Save".
5.  **Install Module:**
    *   Go to **Extensions > Modules**.
    *   Find "AI Product Updater" in the list of modules.
    *   Click the green "Install" button ( `+` icon) next to it.
6.  **Edit Module Settings:**
    *   After installation, click the blue "Edit" button (pencil icon) next to "AI Product Updater".

## Configuration

Once you access the module's edit page:

1.  **Status:**
    *   Set to "Enabled" to activate the module's functionality.
    *   Set to "Disabled" to deactivate it.
2.  **Openrouter API Key:**
    *   Enter your valid Openrouter.ai API Key into the designated field. This is **required** for the module to work if the status is "Enabled".
3.  **Save Settings:**
    *   Click the "Save Settings" button (usually a floppy disk icon or labeled "Save") at the top right or within the settings form.

## Usage

After installation and configuration:

1.  **Navigate:** Go to the "AI Product Updater" module settings page (Extensions > Modules > AI Product Updater > Edit).
2.  **Product Update Tool Section:**
    *   **Fields to Update:** Select one or more product fields you want the AI to generate new content for (e.g., Product Name, Description, Meta Title). Use Ctrl-Click (or Cmd-Click on Mac) to select multiple fields.
    *   **Manual Instructions for AI (Optional):** Provide any specific guidelines, keywords, tone, or context you want the AI to consider. For example: "Focus on eco-friendly aspects", "Use a persuasive tone for a young audience", "Target keywords: sustainable, organic".
    *   **Select Products:**
        *   Use the "Filter by Product Name" field to search for products.
        *   Check the boxes next to the products you wish to update.
3.  **Start Update:**
    *   Click the "**Update Selected Products with AI**" button (usually a play icon) located at the top right of the page.
4.  **Monitor Progress:**
    *   A progress bar will appear, showing the overall status of the update process (e.g., "Processing product X / Y").
    *   Below the progress bar, a **live log output** area will display real-time status updates for each product being processed. This includes:
        *   Confirmation that data is being sent to the AI for a product.
        *   Specific success, warning, or error messages returned for that individual product.
    *   Wait for the process to complete. Each product is processed sequentially.
5.  **Review Results:**
    *   During the process, you can see individual product results in the live log.
    *   Once all products are processed, summary messages (overall success, and lists of any warnings or errors) will be displayed at the top of the page.
    *   Check the updated products on your store's front-end or back-end to review the AI-generated content.
    *   Review OpenCart's error logs (System > Maintenance > Error Logs, look for "AI Updater:" entries) if you encounter issues.

## Logging

*   The module logs detailed information about its operations, which can be helpful for troubleshooting.
*   Logs are written to OpenCart's main system error log file (usually found in `system/storage/logs/`).
*   Look for entries prefixed with `AI Updater:`
    *   **API Requests/Responses:** Details of data sent to and received from the Openrouter API.
    *   **Data Parsing:** Information about how the AI's response is parsed.
    *   **Database Updates:** Confirmation of successful updates or issues encountered.
    *   **Errors:** Any errors that occur during the process.

## Troubleshooting

*   **"Openrouter API Key is missing" error:** Ensure you have entered a valid API key in the module settings and saved it. The module must be "Enabled" for the key to be actively used.
*   **"Permission Denied" error:** Make sure your user group has access and modify permissions for `extension/module/ai_updater` (see Installation Step 4).
*   **Products not updating / API errors:**
    *   Check your Openrouter.ai account for any API key issues, usage limits, or billing problems.
    *   Review the OpenCart error logs for specific error messages from the AI Updater module or the Openrouter API.
    *   Ensure your server's cURL extension is working and can make outbound HTTPS requests.
*   **SEO URLs not changing:** This feature updates SEO URLs when the product name is changed by the AI. Ensure the "Product Name" field is selected for update.

## Future Enhancements (Optional)

*   Option to choose different AI models from Openrouter.
*   More granular control over SEO URL generation (e.g., per language).
*   Directly update product images based on AI analysis or generation (if supported by API).
*   Batch processing settings (e.g., number of products per batch, delay between batches).

---

This `README.md` provides a good starting point. It can be further improved with screenshots or more detailed examples as needed.
