# How to onboard a new merchant

For Edfundo staff. Takes about 10 minutes of your time, plus whatever the merchant needs to finish their steps.

**The short version**

0. *(Recommended for anyone integrating by API or website)* Set up a test account on **staging** first.
1. Collect the trade licence and the owner's email (outside the system).
2. Create the merchant in admin with the welcome email ticked.
3. The merchant sets their password and completes five short steps.
4. You review what they submitted, create their Lean payment destination, and approve.
5. The merchant is live: they can send payment links and take payments.

Until step 4 is done the merchant can sign in and set up products and payers, but **nobody can pay them and they cannot send payment links**.

---

## Step 0. Try it on staging first (recommended)

Staging is a practice copy of Edfundo Pay at **https://staging-pay.edfundo.com**. It is completely separate from production: its own admin, merchants, API keys, webhook secrets and payments. Payments use Lean's **sandbox** (test banks), so **no real money moves**.

| Merchant type | Use staging? |
|---|---|
| Integrating by **API**, **Magento** or a **custom website** | **Yes.** Their developers build and test there before anything goes live |
| Only creating payment links from the **dashboard** | Optional. Useful for a practice run or training: create a link, pay it, watch it show as Paid |

Nothing carries over from staging. When the merchant is ready, you create them again **on production** (Steps 1 to 5 below), and they get a new API key and webhook secret there.

### Set up the staging account

1. Sign in to the **staging** admin (the same admin sign-in as production, but on the staging address, with your staging admin login). Go to **Merchants → New Merchant**.
2. Fill in the name and the merchant's login email as in Step 1. **Use a real mailbox they check:** staging sends real emails, so the welcome email genuinely arrives.
3. Leave **"Email the owner a welcome link"** and **"Account active"** ticked, and **tick "Skip onboarding"**. The staging account is then usable straight away: no trade licence, bank details or approval needed, and payments settle to Lean's test destination automatically.
4. Click **Create Merchant**. The merchant gets a welcome email pointing at staging. They set a password (a **different** one from their production password) and sign in.

> **Want them to see the onboarding screens too?** Leave "Skip onboarding" unticked instead. They complete the steps, you review them as in Step 3, then to approve you need a destination ID and an IBAN on the merchant (Edit). Use Lean's sandbox destination ID (print it on the staging server with `sudo grep LEAN_PAYMENT_DESTINATION_ID /var/www/edfundo-pay/.env`) and any IBAN-format value.

### Make a test payment (everyone)

1. In the merchant's staging dashboard, create a small payment link (for example AED 1.00): **Invoices → New**, or from a product.
2. Open the link and pay it with a Lean **sandbox test bank**. The test bank login is in section 13 of the *Edfundo Pay Merchant API Integration Guide*.
3. Back in the dashboard, check the payment shows as **Paid**.

### For merchants integrating by API or website

1. The owner creates an API key under **Settings → API & Integrations**, and sets their webhook URL and secret in **Settings → Business Profile**. These are **staging-only**: a staging key does not work on production.
2. Send them the **Edfundo Pay Merchant API Integration Guide** (v2.1 or later). Their developers use `https://staging-pay.edfundo.com/api/v1` as the base URL and follow the test sequence in section 13 of the guide.
3. **Webhook sender address:** staging sends from a different server from production. If the merchant's firewall only allows certain IP addresses, give them the staging server's address (find it with `curl -4 -s https://checkip.amazonaws.com` on the staging server), **not** the production one.
4. When their tests pass, set up the production account (Steps 1 to 5) and tell them to switch the base URL, API key and webhook secret over, ideally from environment variables.

### Things to watch on staging

- **Real emails are sent.** Welcome emails, payment links and reminders all go to real addresses. Don't upload real parents or customers on staging; use test addresses you control.
- **Staging is not live.** A merchant working on staging still cannot take real payments. They are live only once approved on production.
- **Clean up.** After go-live, untick **Account active** on the staging account (or delete it) so old test accounts don't pile up.

---

## Before you start: what to collect from the merchant

| What | Why |
|---|---|
| Copy of the **trade licence** | Your own record. The merchant also types the licence number and address in onboarding |
| **Email address for the owner's login** | This becomes their sign-in. Use a real mailbox they check. It can't already be used by another merchant or team member |
| Business name as it should appear to payers | They can edit it themselves during onboarding |

You do **not** need to collect their bank details, contact person or logo. The merchant enters those themselves in onboarding. The welcome email and the first onboarding screen both tell them what to have ready (trade licence, bank details, contact details), so they don't get stuck halfway.

---

## Step 1. Create the merchant (admin)

Admin → **Merchants** → **New Merchant**.

1. **Name:** the registered business name.
2. **Login Email:** the owner's email from above.
3. Leave **"Email the owner a welcome link"** ticked. They choose their own password, so you never share one.
4. Make sure **"Account active"** is ticked. If it isn't, they can't sign in.
5. Set the **Edfundo fee** to the rate you've agreed with the merchant (it starts at the standard 1.5%). **The merchant sees this exact rate on the Fees step and agrees to it**, so get it right now.
6. On production, leave **"Skip onboarding"** unticked. (That box is only for internal or already-set-up accounts. It makes the merchant live immediately.)
7. Anything else (support email, phone, website, bank details) is optional. If you fill it in, it appears pre-filled in the merchant's onboarding, which saves them typing.
8. Click **Create Merchant**.

The owner now gets an email, "Welcome to Edfundo Pay: set your password to get started", with a button. **The link works once and lasts 7 days.**

> If the merchant says they never got it: check spam first, then open the merchant in admin and use **Resend welcome email** (Onboarding card). Sending a new one cancels the old link.

---

## Step 2. What the merchant does

Tell them to expect an email, and what will happen:

1. Click **Set my password** in the email and choose a password.
2. Sign in. The first time, they go straight into onboarding.
3. Five short steps, one per screen. They can press **Save & finish later** on any screen and come back to the same place. A banner on their dashboard shows progress ("2 of 5 steps done") with a **Continue** button.

| Step | What they enter |
|---|---|
| 1. Your business | Business name, support email, support phone, website (optional), logo (optional) |
| 2. Business registration | **Trade licence number**, registered business address, city |
| 3. Where we pay you | Account holder name, bank name, **IBAN** (UAE IBAN, `AE` + 21 digits), **account number**, **SWIFT code** |
| 4. Authorised contact | Name, role, mobile, email of the responsible person. Optional uploads: Emirates ID or passport, bank letter |
| 5. Fees & agreement | Sees their fee percentage and how monthly billing works. Ticks one box to agree. *If monthly billing is switched on for the platform, they also add a card on Telr's secure page here.* |

5. **Review & submit.** They check everything and press **Submit for review**.

You'll get an email, "Onboarding submitted for review", if `ONBOARDING_NOTIFY_EMAIL` is set. The merchant sees a "Thanks, we have your details" page and **cannot edit** until you respond.

Note: while billing is off for the platform, the card isn't asked for. This is normal until billing goes live.

---

## Step 3. Review the submission (admin)

Admin → **Merchants**. A banner at the top shows how many are waiting ("1 merchant waiting for onboarding review"), with a **Show only these** button. Open the merchant. The **Onboarding** card shows everything they submitted: business, bank, contact, the date the agreement was accepted and by whom, and the documents.

**Check:**

- The account holder name matches the trade licence name, and the trade licence number looks right.
- The IBAN looks right and belongs to that business (the bank letter, if uploaded, should show the same IBAN).
- The contact person makes sense for the business.

**If something is wrong:** type exactly what needs to change in the box and click **Send back for changes**. The merchant gets your note by email, sees it on their dashboard and in onboarding, fixes it and resubmits. They keep everything they already entered.

---

## Step 4. Create their Lean destination (admin)

Approval is **refused until the merchant has a Lean destination ID**. Lean is how payments are settled into their bank account.

1. In the merchant's **Onboarding** card, click **create their Lean destination**. This opens the Lean Destinations form **pre-filled** from what the merchant entered: display name, legal account holder name (punctuation removed, as Lean requires), address, city, account number, SWIFT code, IBAN and trade licence number. (You can also reach the form from **Lean Destinations** in the left menu, but then it's blank.)
2. Check the pre-filled details against the bank letter or trade licence. This creates a real destination on Lean's live system, so double-check the IBAN and account number.
3. Submit, then copy the destination ID Lean returns (looks like `dst_a1b2c3d4`).
4. Admin → open the merchant → **Edit**. Paste it into **Lean Payment Destination ID** and save.

While you're in **Edit**, also check:

| Setting | Guidance |
|---|---|
| **Fee percentage** | Should already be right from step 1. If you change it later, the merchant is asked to agree again, and the Onboarding card warns you if they agreed to a different rate. Use **Send back for changes** if they've already submitted |
| **Bill this merchant monthly** | Leave **off** until billing is live for the platform *and* the merchant has registered a card |
| **Let this merchant reconcile their own bank-pending payments** | Optional. Gives them Confirm / Not-received buttons for payments stuck "pending with bank". If off, Edfundo reviews those |
| **Show reward offer** | On by default. Controls the reward banner on their payment pages |

---

## Step 5. Approve

Open the merchant → **Onboarding** card → **Approve** → confirm.

- The merchant is emailed: "You're live on Edfundo Pay".
- Their payment links and product pages now work for payers, and they can send payment links.

**If Approve is refused**, the message says why:

| Message | Fix |
|---|---|
| "Set the Lean destination ID on the merchant first" | Do step 4 |
| "The merchant has no IBAN on file" | They haven't finished the bank step. Send it back or ask them to complete it |
| Lean rejects the destination | Usually a wrong IBAN, account number, SWIFT code or a name with punctuation. Correct it on the form, or send the merchant back to fix their details
| "has not submitted their onboarding for review" | They haven't pressed Submit yet |

---

## After they're live

- **Test it once:** create a small payment link in their account (or ask them to) and pay it with a real or test bank, then check it shows as Paid.
- **Team:** the owner can add colleagues under **Settings → Team** (Owner, Staff or Viewer roles). Nothing for you to do.
- **API keys / Magento:** if they use the Magento module, the owner creates keys under **Settings → API & Integrations**.
- **Webhook:** if they want payment notifications to their own system, they add a URL in **Settings → Business Profile**.

---

## Troubleshooting

| Problem | What to do |
|---|---|
| Merchant says the welcome link doesn't work | It expires after 7 days and works once. Use **Resend welcome email** |
| Merchant can't sign in at all | Check **Account active** in admin. If they've forgotten the password, they use **Forgot password** on the sign-in page |
| Merchant didn't get redirected to onboarding | That only happens at the first sign-in. They can open it any time from the banner on their dashboard |
| Merchant is stuck on step 5 (fees) | Only happens when platform billing is on. They must tick the box and press **Agree & add card**, then finish on Telr's page. If the card fails, they can try again or a different card |
| Merchant wants to change something after submitting | Click **Send back for changes** with a short note. That reopens editing |
| "That email address already has access" when creating | The email is already a merchant login or a team member's. Use a different address |
| Merchant exists already and shouldn't go through onboarding | Create them with **Skip onboarding** ticked (existing live merchants were left untouched when this feature launched) |
| Staging payment won't start ("No Lean payment destination configured") | Staging's `.env` is missing `LEAN_PAYMENT_DESTINATION_ID`, or the merchant has an empty destination. Set it, run `sudo php artisan config:clear` and restart Apache |
| A staging API key gives "unauthorised" on production (or the reverse) | Keys and secrets are separate per environment. Create a new key on the environment they're calling |
| Payers see "This merchant is not able to take payments right now" | The merchant isn't approved yet (or the account is inactive). Approve them, or tick **Account active** |

---

## Quick checklist

- [ ] (API / website merchants) Staging account created, test payment made, webhook and return page tested
- [ ] Trade licence copy and owner email received
- [ ] Merchant created in admin with welcome email ticked and Account active ticked
- [ ] Merchant has set their password and submitted onboarding
- [ ] Details reviewed (name, IBAN, contact)
- [ ] Lean destination created and its ID saved on the merchant
- [ ] Fee percentage correct
- [ ] Approved
- [ ] Test payment confirmed
