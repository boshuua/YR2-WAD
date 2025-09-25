<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrolment Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: #f8f9fa;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 20px 0;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff; border: 1px solid #dee2e6;">
                    <tr>
                        <td align="center" style="background-color: #007bff; padding: 30px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Enrolment Confirmation</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; color: #212529; font-size: 16px;">Hi <?php echo htmlspecialchars($user['first_name']); ?>,</p>
                            <p style="margin: 0 0 20px 0; color: #212529; font-size: 16px; line-height: 1.5;">You are confirmed for the following course. Please find the details below.</p>
                            
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                <tr><td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">Course Title:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #212529; font-weight: 500;"><?php echo htmlspecialchars($course['title']); ?></td></tr>
                                <tr><td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">Starts:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #212529;"><?php echo date('l, jS F Y \a\t H:i', strtotime($course['course_date'])); ?></td></tr>
                                <tr><td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">Duration:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #212529;"><?php echo $duration_formatted; ?></td></tr>
                                <?php if ($course['trainer_id']): ?>
                                <tr><td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">Trainer:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #212529;"><?php echo htmlspecialchars($course['trainer_first_name'] . ' ' . $course['trainer_last_name']); ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color: #f8f9fa; padding: 20px 30px;">
                            <p style="margin: 0; color: #6c757d; font-size: 12px;">© <?php echo date('Y'); ?> Logical View Solutions LTD. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>