# RBAC Implementation Changes

## Overview
This document outlines all changes made to implement Role-Based Access Control (RBAC) with customer and provider roles for both regular authentication and Google OAuth flows.

## Summary of Changes
- Added role selection (Customer/Provider) to signup and Google OAuth flows
- Updated authentication logic to redirect users based on their role
- Created role selection page for new Google OAuth users
- Updated all dashboard redirects to use role-based routing

---

## Files Modified

### 1. `app/Models/User.php`
**Changes:**
- Added `google_id` to the `$fillable` array to allow mass assignment of Google OAuth ID

**Why:**
- Required for storing Google OAuth user IDs when users sign up/login via Google

---

### 2. `app/Http/Controllers/GoogleLoginController.php`
**Changes:**
- Completely refactored `handleGoogleCallback()` method:
  - **Existing users**: Now automatically logs them in and redirects to their role-based dashboard
  - **New users**: Stores Google user data in session and redirects to role selection page
- Added `showRoleSelection()` method: Displays role selection page for new Google OAuth users
- Added `handleRoleSelection()` method: Creates new user account with selected role and redirects appropriately

**Flow:**
1. User clicks "Continue with Google"
2. If user exists → Login and redirect to their dashboard
3. If new user → Show role selection → Create account → Redirect to selected role's dashboard

---

### 3. `routes/web.php`
**Changes:**
- Updated `/signup` POST route:
  - Now accepts `firstName` and `lastName` (instead of just `name`)
  - Validates `user_type` as `customer` or `provider` (instead of `customer` or `business`)
  - Converts role to integer: `customer = 1`, `provider = 0`
  - Redirects to appropriate dashboard based on selected role
- Updated `/login` POST route:
  - Now redirects based on user's existing `user_type` in database
  - Providers (0) → `dashboard.provider`
  - Customers (1) → `home`
- Updated `/dashboard` GET route:
  - Fixed comparison to use integer values (0 for provider, 1 for customer)
- Added new Google OAuth routes:
  - `GET /auth/google/select-role` - Shows role selection page
  - `POST /auth/google/select-role` - Handles role selection submission

---

### 4. `resources/views/auth/signup.blade.php`
**Changes:**
- Added role selection section with two radio button options:
  - **Customer**: "Browse and book services"
  - **Service Provider**: "Offer your services to customers"
- Each option includes descriptive text and styling
- Role selection is required before form submission

**UI:**
- Radio buttons with hover effects
- Clear descriptions for each role
- Maintains existing form styling and validation

---

## Files Created

### 1. `resources/views/auth/select-role.blade.php`
**Purpose:**
- Role selection page for new users signing up via Google OAuth
- Shown after Google authentication but before account creation

**Features:**
- Two role options with detailed descriptions:
  - **Customer**: Browse and book services
  - **Service Provider**: Offer services and manage bookings
- Each option shows benefits/features
- Clean, user-friendly interface matching the app's design
- Form validation and error handling
- Back to login link

---

## User Type Mapping

The system uses integer values for `user_type` in the database:
- `0` = Provider (Service Provider/Business)
- `1` = Customer (Regular User)

This matches the existing migration structure.

---

## Authentication Flows

### Regular Signup Flow
1. User visits `/signup`
2. Fills in: First Name, Last Name, Email, Password, Confirm Password
3. **Selects role**: Customer or Provider
4. Submits form
5. Account created with selected role
6. User logged in automatically
7. Redirected to:
   - Customer → `/home`
   - Provider → `/provider/dashboard`

### Regular Login Flow
1. User visits `/login`
2. Enters email and password
3. System checks existing `user_type` in database
4. User logged in
5. Redirected to:
   - Customer (user_type = 1) → `/home`
   - Provider (user_type = 0) → `/provider/dashboard`

### Google OAuth - New User Flow
1. User clicks "Continue with Google" on login/signup page
2. Completes Google authentication
3. System checks if email exists in database
4. **If new user:**
   - Google user data stored in session
   - Redirected to `/auth/google/select-role`
   - User selects role (Customer or Provider)
   - Account created with selected role
   - User logged in automatically
   - Redirected to appropriate dashboard

### Google OAuth - Existing User Flow
1. User clicks "Continue with Google"
2. Completes Google authentication
3. System finds existing user by email
4. Google ID updated if missing
5. User logged in automatically
6. Redirected to their existing role's dashboard:
   - Customer → `/home`
   - Provider → `/provider/dashboard`

---

## Dashboard Routes

All dashboard routes now properly redirect based on `user_type`:

- **Customer Dashboard**: `/home` (route: `home`)
- **Provider Dashboard**: `/provider/dashboard` (route: `dashboard.provider`)
- **General Dashboard**: `/dashboard` (route: `dashboard`) - Auto-redirects based on role

---

## Testing Checklist

### Regular Signup
- [ ] Sign up as Customer → Should redirect to `/home`
- [ ] Sign up as Provider → Should redirect to `/provider/dashboard`
- [ ] Verify role is saved correctly in database

### Regular Login
- [ ] Login as existing Customer → Should redirect to `/home`
- [ ] Login as existing Provider → Should redirect to `/provider/dashboard`

### Google OAuth - New User
- [ ] Sign up with Google (new email) → Should show role selection
- [ ] Select Customer → Should create account and redirect to `/home`
- [ ] Select Provider → Should create account and redirect to `/provider/dashboard`
- [ ] Verify Google ID is saved in database

### Google OAuth - Existing User
- [ ] Login with Google (existing email) → Should login and redirect to correct dashboard
- [ ] Verify Google ID is updated if missing

### Edge Cases
- [ ] Try to access role selection without Google session → Should redirect to login
- [ ] Verify form validation works (required fields, email format, etc.)
- [ ] Test with users who have both regular and Google auth

---

## Database Schema

The `users` table should have:
- `user_type` (integer): `0` = Provider, `1` = Customer
- `google_id` (string, nullable): Google OAuth ID
- `password` (string, nullable): Can be null for Google-only users

---

## Notes

1. **Role Selection**: Users must select a role during signup. The system does not support dual roles (being both customer and provider) - a user must choose one primary role.

2. **Google OAuth**: New users via Google OAuth will always see the role selection page. Existing users are automatically logged in and redirected.

3. **Session Management**: Google OAuth user data is temporarily stored in session during the role selection process and cleared after account creation.

4. **Backward Compatibility**: Existing users in the database will continue to work. The system checks their `user_type` value and redirects accordingly.

5. **Password Handling**: Google OAuth users get a random password hash (they won't need it for login).

---

## Migration Notes

No new migrations are required. The existing migration `2025_11_25_000936_add_user_type_to_users_table.php` already supports:
- `user_type` as integer (0 = business/provider, 1 = customer)
- `google_id` as nullable string

---

## Future Enhancements (Optional)

- Allow users to have both customer and provider roles
- Add role switching functionality
- Add role verification/permissions middleware
- Add role-based feature flags

---

**Last Updated**: December 2024
**Author**: RBAC Implementation
**Version**: 1.0

