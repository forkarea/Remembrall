CREATE OR REPLACE FUNCTION unit_tests.harassed_on_too_many_attempts() RETURNS TEST_RESULT AS $$
DECLARE
	message TEST_RESULT;
	actual BOOLEAN;
	expected CONSTANT BOOLEAN DEFAULT TRUE;
	email CONSTANT CITEXT DEFAULT 'foo@email.cz';
	subscription CONSTANT INTEGER DEFAULT 2;
BEGIN
	PERFORM truncate_tables('postgres');
	PERFORM restart_sequences();

	INSERT INTO participants (email, subscription_id, code, invited_at, accepted, decided_at) VALUES
	(email, subscription, 'abc', NOW(), FALSE, NULL);

	TRUNCATE invitation_attempts;

	INSERT INTO invitation_attempts (attempt_at, participant_id) VALUES
	(NOW(), 1),
	(NOW(), 1),
	(NOW(), 1),
	(NOW(), 1),
	(NOW(), 1);

	SELECT is_invitation_harassed(subscription, email)
	INTO actual;
	IF actual = expected
	THEN
		SELECT assert.ok('OK')
		INTO message;
	END IF;
	RETURN message;
END
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION unit_tests.not_harassed_on_low_count_of_attempts() RETURNS TEST_RESULT AS $$
DECLARE
	message TEST_RESULT;
	actual BOOLEAN;
	expected CONSTANT BOOLEAN DEFAULT FALSE;
	email CONSTANT CITEXT DEFAULT 'foo@email.cz';
	subscription CONSTANT INTEGER DEFAULT 2;
BEGIN
	PERFORM truncate_tables('postgres');
	PERFORM restart_sequences();

	INSERT INTO participants (email, subscription_id, code, invited_at, accepted, decided_at) VALUES
	(email, subscription, 'abc', NOW(), FALSE, NULL);

	TRUNCATE invitation_attempts;

	INSERT INTO invitation_attempts (attempt_at, participant_id) VALUES
	(NOW(), 1),
	(NOW(), 1),
	(NOW(), 1),
	(NOW(), 1);

	SELECT is_invitation_harassed(subscription, email)
	INTO actual;
	IF actual = expected
	THEN
		SELECT assert.ok('OK')
		INTO message;
	END IF;
	RETURN message;
END
$$
LANGUAGE plpgsql;