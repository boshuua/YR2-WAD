<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrolment Confirmation</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap');
        body { margin: 0; padding: 0; font-family: 'Be Vietnam Pro', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #007bff; color: #ffffff; padding: 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 30px 40px; }
        .content p { color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 20px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table td { padding: 12px 0; border-bottom: 1px solid #e9ecef; }
        .details-table .label { color: #888; font-weight: 600; width: 120px; }
        .details-table .value { color: #333; font-weight: 500; }
        .footer { background-color: #343a40; color: #adb5bd; padding: 20px; text-align: center; font-size: 12px; }
        .footer a { color: #ffffff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Enrolment Confirmed!</h1>
        </div>
        <div class="content">
            <p>Hi <?php echo htmlspecialchars($user['first_name']); ?>,</p>
            <p>This email confirms your enrolment for the course detailed below. We look forward to seeing you!</p>
            <table class="details-table">
                <tr>
                    <td class="label">Course Title:</td>
                    <td class="value"><?php echo htmlspecialchars($course['title']); ?></td>
                </tr>
                <tr>
                    <td class="label">Starts:</td>
                    <td class="value"><?php echo date('l, jS F Y \a\t H:i', strtotime($course['course_date'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Duration:</td>
                    <td class="value"><?php echo $duration_formatted; ?></td>
                </tr>
                <?php if ($course['trainer_id']): ?>
                <tr>
                    <td class="label">Trainer:</td>
                    <td class="value"><?php echo htmlspecialchars($course['trainer_first_name'] . ' ' . $course['trainer_last_name']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="footer">
            Logical View Solutions LTD &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>