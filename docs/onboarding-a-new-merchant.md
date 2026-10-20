# How to onboard a new merchant

For Edfundo staff. Takes about 10 minutes of your time, plus whatever the merchant needs to finish their steps.

**The short version**

1. Collect the trade licence and the owner's email (outside the system).
2. Create the merchant in admin with the welcome email ticked.
3. The merchant sets their password and completes five short steps.
4. You review what they submitted, create their Lean payment destination, and approve.
5. The merchant is live: they can send payment links and take payments.

Until step 4 is done the merchant can sign in and set up products and payers, but **nobody can pay them and they cannot send payment links**.

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
6. Leave **"Skip onboarding"** unticked. (That box is only for internal or already-set-up accounts. It makes the merchant live immediately.)
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
| Payers see "This merchant is not able to take payments right now" | The merchant isn't approved yet (or the account is inactive). Approve them, or tick **Account active** |

---

## Quick checklist

- [ ] Trade licence copy and owner email received
- [ ] Merchant created in admin with welcome email ticked and Account active ticked
- [ ] Merchant has set their password and submitted onboarding
- [ ] Details reviewed (name, IBAN, contact)
- [ ] Lean destination created and its ID saved on the merchant
- [ ] Fee percentage correct
- [ ] Approved
- [ ] Test payment confirmed
