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
*   **OCMOD Installer Compatible:** Can be installed via OpenCart's Extension Installer.

## Requirements

*   OpenCart 3.0.x (e.g., 3.0.3.6, 3.0.3.8)
*   An active Openrouter.ai API Key.
*   PHP cURL extension enabled on your server.

## Installation

1.  **Download:** Obtain the `AI_Urun_Guncelleme_vX.X.X.ocmod.zip` file.
2.  **Upload via Extension Installer:**
    *   In your OpenCart Admin Panel, navigate to **Extensions > Installer**.
    *   Click the "Upload" button and select the downloaded `.ocmod.zip` file.
3.  **Refresh Modifications:**
    *   Go to **Extensions > Modifications**.
    *   Click the blue "Refresh" button (top right).
4.  **Permissions:**
    *   Go to **System > Users > User Groups**.
    *   Select your user group (e.g., "Administrator") and click "Edit".
    *   Under "Access Permission" and "Modify Permission", find and check the boxes for:
        *   `extension/module/ai_updater`
    *   Click "Save".
5.  **Install Module:**
    *   Go to **Extensions > Modules**.
    *   Find "AI Ürün Güncelleme" (or "AI Product Updater" depending on your default language settings for the admin) in the list.
    *   Click the green "Install" button ( `+` icon) next to it.
6.  **Edit Module Settings:**
    *   After installation, click the blue "Edit" button (pencil icon) next to "AI Ürün Güncelleme".

## Configuration

Once you access the module's edit page:

1.  **Status:**
    *   Set to "Enabled" to activate the module's functionality.
    *   Set to "Disabled" to deactivate it.
2.  **Openrouter API Key:**
    *   Enter your valid Openrouter.ai API Key into the designated field. This is **required** for the module to work if the status is "Enabled".
3.  **Save Settings:**
    *   Click the "Save Settings" button.

## Usage

After installation and configuration:

1.  **Navigate:** Go to the "AI Ürün Güncelleme" module settings page (Extensions > Modules > AI Ürün Güncelleme > Edit or via the left-hand menu link if the OCMOD modification for the menu is active).
2.  **Product Update Tool Section:**
    *   **Fields to Update:** Select one or more product fields.
    *   **Manual Instructions for AI (Optional):** Provide specific guidelines.
    *   **Select Products:** Filter and select products.
3.  **Start Update:** Click the "**Update Selected Products with AI**" button.
4.  **Monitor Progress:**
    *   A progress bar will show overall status.
    *   Below it, a live log will display real-time status for each product.
5.  **Review Results:**
    *   Individual results appear in the live log.
    *   Summary messages are shown at the top after completion.
    *   Review OpenCart's error logs (System > Maintenance > Error Logs) for "AI Updater:" entries if issues occur.

## Logging

*   Logs are written to OpenCart's main system error log file (`system/storage/logs/`).
*   Look for entries prefixed with `AI Updater:`.

## Troubleshooting

*   **"Openrouter API Key is missing" / "API Key is required" error:** Ensure a valid API key is saved in module settings and the module is enabled.
*   **"Permission Denied" error:** Check user group permissions.
*   **Menu link not appearing:** Ensure Modifications are refreshed after installing the `.ocmod.zip`.
*   **Products not updating / API errors:** Check API key, Openrouter account, server cURL, and OpenCart error logs.

---
Bu `README.md` dosyası, OCMOD kurulumunu ve genel kullanımı kapsar.
