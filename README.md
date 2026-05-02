# CiviCRM: FPPTA Event Registration Denial
## com.joineryhq.fpptaregdeny

Custom for FPPTA: 

Deny access to event regisration in certain cases, even when user has sufficient permission.

## Details

If a user, who has permission to register online for events, attempts to do so,
this extension will check whether the user meets certain criteria. If the user
does not meet all relevant criteria, this extensino will prevent them from loading 
the event registration form, and will instead redirect them to the event info
page, with a clear message indicating why they are not allowed to register.

## Configuration Settings

Administer > System Settings > FPPTA Event Registration Denial Settings

## Administrator "Check Permissions" page

For any CiviCRM contact, this extension adds a button/link labeled "User can register self?",
under that contact's Events tab. Clicking this button/link will display a summary
status page indicating whether the contact would be prevented from online event 
registration by this extension, including details as to that contact's qualification
according to each criteria.

## License

The extension is licensed under [GPL-3.0](LICENSE.txt).

## Support

Support for this package is handled under Joinery's ["Limited Support" policy](https://joineryhq.com/software-support-levels#limited-support).

Public issue queue for this package: [https://github.com/JoineryHQ/com.joineryhq.fpptaregdeny/issues](https://github.com/JoineryHQ/com.joineryhq.fpptaregdeny/issues)

