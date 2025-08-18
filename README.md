# LMB Core Plugin

LMB Core is a comprehensive WordPress plugin designed to create a full-featured legal ads platform with a strong focus on Elementor integration. It provides a robust backend system for managing custom post types, user roles, a points-based economy, and automated PDF generation, along with a suite of Elementor widgets for building a dynamic frontend experience for both administrators and clients.

## Core Features

-   **Custom Post Types**: Establishes dedicated post types for `Legal Ads`, `Newspapers`, `Payments`, and `Packages`.
-   **Points System**: A complete credit-based system allowing users to purchase packages and spend points on services. All transactions are logged for administrative review.
-   **Elementor Integration**: At its heart, the plugin is built to be controlled via Elementor, providing a rich set of widgets to build out user dashboards and administrative interfaces with ease.
-   **PDF Generation**: Automatically generates PDF invoices for package purchases and official PDF versions of published legal ads using the FPDF library.
-   **Frontend AJAX Interactions**: Enhances user experience with AJAX-powered actions, such as submitting ads for review and generating invoices without page reloads.
-   **Custom User Roles**: Introduces `Client` and `Employee` roles to create a structured permission system for your platform.
-   **Admin & User Dashboards**: Provides all the necessary tools and data points to construct detailed dashboards for both administrators and clients.
-   **Notification System**: A built-in system to manage and display user notifications, complete with read/unread status and email alerts for important events like ad approval or payment verification.

## Available Elementor Widgets

Below is a list of all the custom Elementor widgets provided by the LMB Core plugin. These can be found in the Elementor editor under the "LMB Core Widgets" category.

| Widget Name | Widget Slug | Description |
| :--- | :--- | :--- |
| **LMB Admin Actions & Feeds** | `lmb_admin_actions` | An essential dashboard component for administrators. It displays quick action buttons and live feeds of pending ads and payments requiring review. |
| **LMB Admin Stats & Overview**| `lmb_admin_stats` | Displays key statistics for the entire platform, such as total clients, ads, newspapers, and revenue. For admin dashboards only. |
| **LMB Ads Directory** | `lmb_ads_directory` | Creates a public, searchable, and filterable directory of all published legal ads, complete with pagination. |
| **LMB Invoice Generator** | `lmb_invoice_widget` | A simple widget that provides a button to generate a generic PDF invoice. Primarily used as a fallback or for miscellaneous payments. |
| **LMB Newspaper Directory** | `lmb_newspaper_directory`| Creates a public, searchable directory of all uploaded newspaper editions, with thumbnails and download links. |
| **LMB Notifications** | `lmb_notifications` | A user-facing notification bell and dropdown menu that displays recent account alerts and system messages. |
| **LMB Packages Pricing Table**| `lmb_subscribe_package`| Displays available point packages in a clean pricing table format. Users can click to generate an invoice for a bank transfer payment. |
| **LMB Upload Bank Proof** | `lmb_upload_bank_proof`| A form for logged-in users to upload proof of payment (e.g., a bank transfer receipt) after generating an invoice. |
| **LMB Upload Newspaper** | `lmb_upload_newspaper`| A comprehensive form for administrators to upload new newspaper editions, including the title, date, PDF file, and an optional thumbnail. |
| **LMB User Stats** | `lmb_user_stats` | Displays a grid of key statistics for the currently logged-in user, including their points balance, total submitted ads, and the status of their ads. |

## Available Shortcodes

For developers who prefer to build outside of Elementor or need to embed functionality in other areas, the following shortcodes are available.

| Shortcode | Description | Example Usage |
| :--- | :--- | :--- |
| **`[lmb_user_stats]`** | Displays the user statistics widget, showing the logged-in user their points balance and ad counts. | `[lmb_user_stats]` |
| **`[lmb_user_charts]`** | Renders a line chart displaying the logged-in user's points usage over the current year. | `[lmb_user_charts]` |
| **`[lmb_user_ads_list]`** | Shows the logged-in user a list of their most recent legal ads and their current status (Draft, Pending, Published, Denied). | `[lmb_user_ads_list]` |
| **`[lmb_user_total_ads]`** | A simple shortcode that outputs the total number of ads submitted by the current user. | `You have submitted [lmb_user_total_ads] ads.` |
| **`[lmb_user_balance]`** | A simple shortcode that outputs the current points balance for the logged-in user. | `Your balance is: [lmb_user_balance] points.` |
| **`[lmb_ads_directory]`** | Displays the main legal ads directory. This is a fallback for the Elementor widget. | `[lmb_ads_directory]` |
| **`[lmb_newspaper_directory]`**| Displays the main newspaper directory. This is a fallback for the Elementor widget. | `[lmb_newspaper_directory]` |

## How It Works: The Ad Publication Flow

1.  **Submission**: A user with the `Client` role fills out an Elementor form on the frontend. This form is configured with the "Save as Legal Ad" action, which creates a new `lmb_legal_ad` post with a `draft` status.
2.  **Review Request**: From their personal dashboard, the client can see their draft ad and click a button to submit it for review, changing its status to `pending_review`.
3.  **Admin Review**: An administrator sees the pending ad in their dashboard's "Pending Legal Ads" feed. They can approve or deny it directly from this feed.
4.  **Approval & Points Deduction**: If approved, the system automatically deducts the required number of points from the client's balance. If the balance is insufficient, the ad is automatically denied.
5.  **PDF Generation & Publication**: Upon successful point deduction, the ad's status changes to `published`, and a final PDF version of the ad is generated and attached to the post. An invoice for the point transaction is also created.
6.  **Notification**: The client receives an on-site notification and an email informing them that their ad has been approved and published.