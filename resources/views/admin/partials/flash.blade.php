{{--
    Admin flash + validation feedback block.
    Kept as a compatibility include: every screen that already includes this partial
    now renders the shared Feedback_Channel (`x-admin.feedback`), which owns the
    success/failure/validation contract, ARIA roles and dismiss control.
    Expects: session('success'), session('error'), $errors.
--}}
<x-admin.feedback />
