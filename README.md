# Ho_Customer

### Features:
- Automatically creates customers from guest orders. This also links guest orders to customers if the email address matches with an account.
- Adds better support for the customer increment_id (the customer/create_account/generate_human_friendly_id setting)

### Convert all existing orders to customers:
There is a non-scheduled cron job which can be scheduled to convert all existing 
