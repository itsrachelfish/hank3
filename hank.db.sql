CREATE TABLE tell(server_channel text, nick_to text, nick_from text, msg text, ts int);
CREATE TABLE weather(server_channel text, nick text, opts text, ts int, primary key(server_channel, nick));