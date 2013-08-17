#
#
# Name of the plugin
package anomalia;

# highly recommended for good style Perl programming
use strict;

# Link used modules
use Sys::Syslog;
use POSIX;
use DBI;
use Storable;

# This string identifies the version of the plugin (e.g. 130 means 1.3.0).
our $VERSION = 100;

our % cmd_lookup = (
	"feed_graph"      => \&feedGraph,
	"get_sqlite_data" => \&GetDataFromSqlite,
	"get_sqlite_syn" => \&GetDataFromSqliteSYN,
	"get_sqlite_synclosed" => \&GetDataFromSqliteSYNCLOSED,
	"get_sqlite_null" => \&GetDataFromSqliteNULL,
	"get_sqlite_udp" => \&GetDataFromSqliteUDP,
	"save_settings"   => \&SaveSettings,
	"load_settings"   => \&LoadSettings,
);


sub GetSettings {

        &CheckSettings;

	# Initialize the name of settings file
        my $path = $NfConf::BACKEND_PLUGINDIR;
        my $file = "$path/anomalia/anomalia.conf";

	# Load settings from file
	my $opts = retrieve($file);
	
        return %$opts;
}

sub CheckSettings {

	# Initialize the name of settings file
        my $path = $NfConf::BACKEND_PLUGINDIR;
        my $file = "$path/anomalia/anomalia.conf";
	my %opts;

    # If the file doesn't exist, create it with default values
	if( ! ( -e $file ) ){
		%opts = (
			settings_syn_probes => "10",
			settings_synclosed_probes => "10",
			settings_udp_probes => "10",
			settings_null_probes => "10"
		);
		store \%opts, $file;
	}
}

sub LoadSettings {

        &CheckSettings;
        my      $socket = shift;
        my      $opts = shift;

	# Initialize the name of settings file
        my $path = $NfConf::BACKEND_PLUGINDIR;
        my $file = "$path/anomalia/anomalia.conf";
	my %opts;

	# Load settings from file
	$opts = retrieve($file);
	
	# Sent settings to frontend
        Nfcomm::socket_send_ok ($socket, \%$opts);

} # End of LoadSettings


sub SaveSettings {

        my      $socket = shift;
        my      $opts = shift;

	# Initialize the name of settings file
        my $path = $NfConf::BACKEND_PLUGINDIR;
        my $file = "$path/anomalia/anomalia.conf";

	# Save settings to the file
	store \%$opts, $file;

        # Send "0" to the frontend
        my %args;
        $args{'settings_saved'} = "0";
        Nfcomm::socket_send_ok ($socket, \%args);

} # End of SaveSettings

	
# Connect to the SQLite DB using settings in nfsen.conf
#
sub ConnectToSqlite {
        my $dbconf = $NfConf::PluginConf{anomalia};
        my $db_file = $$dbconf{'db_file'};
        return my $dbh = DBI->connect ( "dbi:SQLite:dbname=$db_file", "", "") or die "Cannot connect to database! " . DBI->errstr;

} # end of ConnectToSqlite

# Create data table in SQLite if it doesn't exists
#
sub CreateSqliteTable {

        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT name FROM sqlite_master WHERE type='table' AND name='suspiciousSyn'");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
	$sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
        if ( ! $sth->fetchrow()) {
                $sth->finish;
    		my $sth_syn = $dbh->prepare('CREATE TABLE suspiciousSyn (id INTEGER NOT NULL PRIMARY KEY
						ASC AUTOINCREMENT,
				 timestamp INT,
                                 source_ip TEXT,
                                 source_port TEXT,
                                 destination_port TEXT,
                                 probes INTEGER
                                );'
        		    );
                if ( !defined $sth_syn ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth_syn->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
        	$sth_syn->finish;

    		my $sth_synclosed = $dbh->prepare('CREATE TABLE suspiciousSynClosed (id INTEGER NOT NULL PRIMARY KEY
						ASC AUTOINCREMENT,
				 timestamp INT,
                                 source_ip TEXT,
                                 source_port TEXT,
                                 destination_port TEXT,
                                 probes INTEGER
                                );'
           		);
                if ( !defined $sth_synclosed ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth_synclosed->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
        	$sth_synclosed->finish;

    		my $sth_null = $dbh->prepare('CREATE TABLE suspiciousNull (id INTEGER NOT NULL PRIMARY KEY
						ASC AUTOINCREMENT,
				 timestamp INT,
                                 source_ip TEXT,
                                 source_port TEXT,
                                 destination_port TEXT,
                                 probes INTEGER
                                );'
            		);
                if ( !defined $sth_null ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth_null->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
 	        $sth_null->finish;

    		my $sth_udp = $dbh->prepare('CREATE TABLE suspiciousUdp (id INTEGER NOT NULL PRIMARY KEY
						ASC AUTOINCREMENT,
				 timestamp INT,
                                 source_ip TEXT,
                                 source_port TEXT,
                                 destination_port TEXT,
                                 probes INTEGER
                                );'
           		 );
                if ( !defined $sth_udp ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth_udp->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
        	$sth_udp->finish;

		my $sth = $dbh->prepare( "CREATE TABLE data (timestamp INT,type INT, score_min INT, score_alert INT);");
                if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
                $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
		$sth->finish;
        }
        $sth->finish;
        $dbh->disconnect;

} # end of CreateSqliteTable

# Get data from SQLite database table
#
sub GetDataFromSqlite {
	my $socket  = shift;    # scalar
	my $opts    = shift;    # reference to a hash
	#my $type    = $opts{type};    # type of graph
	my $type    = 2;

        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT timestamp,score_min,score_alert FROM data WHERE type = $type ORDER BY timestamp DESC limit 20;");
        if ( !defined $sth ) { syslog("info", "anomalia: Cannot prepare statement: $DBI::errstr\n "); die; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

        my @timestamp = ();
        my @score_min = ();
        my @score_alert = ();
        my @result_tmp = ();

        # save table columns to separate arrays
        while ( (@result_tmp ) = $sth->fetchrow()){
                push @timestamp, $result_tmp[0];
                push @score_min, $result_tmp[1];
                push @score_alert, $result_tmp[2];
        }

        $dbh->disconnect;

        my %args;
        $args{'timestamp'} = \@timestamp;
        $args{'score_min'} = \@score_min;
        $args{'score_alert'} = \@score_alert;
        Nfcomm::socket_send_ok ($socket, \%args);

}

sub GetDataFromSqliteSYN {
        my $socket  = shift;    # scalar
        my $opts    = shift;    # reference to a hash
        #my $type    = $opts{type};    # type of graph
        my $type    = 0;
                
        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT timestamp,score_min,score_alert FROM data WHERE type = $type ORDER BY timestamp DESC limit 20;");
        if ( !defined $sth ) { syslog("info", "anomalia: Cannot prepare statement: $DBI::errstr\n "); die; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

        my @timestamp = ();
        my @score_min = ();
        my @score_alert = ();
        my @result_tmp = ();

        # save table columns to separate arrays
        while ( (@result_tmp ) = $sth->fetchrow()){
                push @timestamp, $result_tmp[0];
                push @score_min, $result_tmp[1];
                push @score_alert, $result_tmp[2];
        }

        $dbh->disconnect;

        my %args;
        $args{'timestamp'} = \@timestamp;
        $args{'score_min'} = \@score_min;
        $args{'score_alert'} = \@score_alert;
        Nfcomm::socket_send_ok ($socket, \%args);

}


sub GetDataFromSqliteSYNCLOSED {
        my $socket  = shift;    # scalar
        my $opts    = shift;    # reference to a hash
        #my $type    = $opts{type};    # type of graph
        my $type    = 1;

        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT timestamp,score_min,score_alert FROM data WHERE type = $type ORDER BY timestamp DESC limit 20;");
        if ( !defined $sth ) { syslog("info", "anomalia: Cannot prepare statement: $DBI::errstr\n "); die; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

        my @timestamp = ();
        my @score_min = ();
        my @score_alert = ();
        my @result_tmp = ();

        # save table columns to separate arrays
        while ( (@result_tmp ) = $sth->fetchrow()){
                push @timestamp, $result_tmp[0];
                push @score_min, $result_tmp[1];
                push @score_alert, $result_tmp[2];
        }

        $dbh->disconnect;

        my %args;
        $args{'timestamp'} = \@timestamp;
        $args{'score_min'} = \@score_min;
        $args{'score_alert'} = \@score_alert;
        Nfcomm::socket_send_ok ($socket, \%args);

}

sub GetDataFromSqliteUDP {
        my $socket  = shift;    # scalar
        my $opts    = shift;    # reference to a hash
        #my $type    = $opts{type};    # type of graph
        my $type    = 2;

        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT timestamp,score_min,score_alert FROM data WHERE type = $type ORDER BY timestamp DESC limit 20;");
        if ( !defined $sth ) { syslog("info", "anomalia: Cannot prepare statement: $DBI::errstr\n "); die; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

        my @timestamp = ();
        my @score_min = ();
        my @score_alert = ();
        my @result_tmp = ();

        # save table columns to separate arrays
        while ( (@result_tmp ) = $sth->fetchrow()){
                push @timestamp, $result_tmp[0];
                push @score_min, $result_tmp[1];
                push @score_alert, $result_tmp[2];
        }

        $dbh->disconnect;

        my %args;
        $args{'timestamp'} = \@timestamp;
        $args{'score_min'} = \@score_min;
        $args{'score_alert'} = \@score_alert;
        Nfcomm::socket_send_ok ($socket, \%args);

}

sub GetDataFromSqliteNULL {
        my $socket  = shift;    # scalar
        my $opts    = shift;    # reference to a hash
        #my $type    = $opts{type};    # type of graph
        my $type    = 3;

        my $dbh = &ConnectToSqlite;
        my $sth = $dbh->prepare( "SELECT timestamp,score_min,score_alert FROM data WHERE type = $type ORDER BY timestamp DESC limit 20;");
        if ( !defined $sth ) { syslog("info", "anomalia: Cannot prepare statement: $DBI::errstr\n "); die; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

        my @timestamp = ();
        my @score_min = ();
        my @score_alert = ();
        my @result_tmp = ();

        # save table columns to separate arrays
        while ( (@result_tmp ) = $sth->fetchrow()){
                push @timestamp, $result_tmp[0];
                push @score_min, $result_tmp[1];
                push @score_alert, $result_tmp[2];
        }

        $dbh->disconnect;

        my %args;
        $args{'timestamp'} = \@timestamp;
        $args{'score_min'} = \@score_min;
        $args{'score_alert'} = \@score_alert;
        Nfcomm::socket_send_ok ($socket, \%args);

}

	
#
# feedGraph can be called from frontend as feed_graph function. It takes two
# arguments: first $socket, which is handle of communication socket to the
# frontend and second $opts array, which contains options sent from frontend.
#
# Which data will be sent to fronted depends on the value of "graph_name"
# option in $opts array.
#
sub feedGraph {
        my $socket      = shift;
        my $opts        = shift;
        my $graph_name  = $$opts{"graph_name"};
        my $begin       = $$opts{"begin"};
        my $end         = $$opts{"end"};

        # Default values
        my @line1 = (1,1);
        my @line2 = (1,1);


	if ( $graph_name eq "flot_syndata_sample" ) {
		@line1 = (); 
		@line2 = (); 
		# Return random values to the sample graph
		for (my $i = $begin; $i <= $end; $i+=300) {
			my $random_number;
			$random_number = rand(100);
			push(@line1, $random_number);
			$random_number = rand(100) + 200;
			push(@line2, $random_number);
		} 
	}

	if ( $graph_name eq "flot_syncloseddata_sample" ) {
		@line1 = (); 
		@line2 = (); 
		# Return random values to the sample graph
		for (my $i = $begin; $i <= $end; $i+=300) {
			my $random_number;
			$random_number = rand(100);
			push(@line1, $random_number);
			$random_number = rand(100) + 200;
			push(@line2, $random_number);
		} 
	}

	if ( $graph_name eq "flot_nulldata_sample" ) {
		@line1 = (); 
		@line2 = (); 
		# Return random values to the sample graph
		for (my $i = $begin; $i <= $end; $i+=300) {
			my $random_number;
			$random_number = rand(100);
			push(@line1, $random_number);
			$random_number = rand(100) + 200;
			push(@line2, $random_number);
		} 
	}

	if ( $graph_name eq "flot_udpdata_sample" ) {
		@line1 = (); 
		@line2 = (); 
		# Return random values to the sample graph
		for (my $i = $begin; $i <= $end; $i+=300) {
			my $random_number;
			$random_number = rand(100);
			push(@line1, $random_number);
			$random_number = rand(100) + 200;
			push(@line2, $random_number);
		} 
	}
if ( $graph_name eq "highchart_syndata_sample" ) {


	# Connect to the Sqlite database
	my $dbh = &ConnectToSqlite;

	# Get requested data from the database
	my $sth = $dbh->prepare( "SELECT timestamp,score_min from data WHERE timestamp >= $begin and timestamp <= $end AND type = 0;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_min = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	my @result_tmp = ();
	while ( (@result_tmp ) = $sth->fetchrow()){
		$score_min{$result_tmp[0]} = $result_tmp[1];
	}

	# Get requested data from the database
	my $sth_alert = $dbh->prepare( "SELECT timestamp,score_alert from data WHERE timestamp >= $begin and timestamp <= $end AND type = 0;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth_alert->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_alert = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	@result_tmp = ();
	while ( (@result_tmp ) = $sth_alert->fetchrow()){
		$score_alert{$result_tmp[0]} = $result_tmp[1];
	}

	# Disconnect the Sqlite database
	$dbh->disconnect;

	# Convert requested data to the graph points. It means check if there is
	# data point for each timeslot in given interval and put NULL value instead
	# of missing values 
	@line1 = ();
	@line2 = ();
	for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line1, $score_min{$timeslot});
		} else {
			push(@line1, "null");
		}
	}
		for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line2, $score_alert{$timeslot});
		} else {
			push(@line2, "null");
		}
	}

}

if ( $graph_name eq "highchart_syncloseddata_sample" ) {


	# Connect to the Sqlite database
	my $dbh = &ConnectToSqlite;

	# Get requested data from the database
	my $sth = $dbh->prepare( "SELECT timestamp,score_min from data WHERE timestamp >= $begin and timestamp <= $end AND type = 1;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_min = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	my @result_tmp = ();
	while ( (@result_tmp ) = $sth->fetchrow()){
		$score_min{$result_tmp[0]} = $result_tmp[1];
	}

	# Get requested data from the database
	my $sth_alert = $dbh->prepare( "SELECT timestamp,score_alert from data WHERE timestamp >= $begin and timestamp <= $end AND type = 1;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth_alert->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_alert = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	@result_tmp = ();
	while ( (@result_tmp ) = $sth_alert->fetchrow()){
		$score_alert{$result_tmp[0]} = $result_tmp[1];
	}

	# Disconnect the Sqlite database
	$dbh->disconnect;

	# Convert requested data to the graph points. It means check if there is
	# data point for each timeslot in given interval and put NULL value instead
	# of missing values 
	@line1 = ();
	@line2 = ();
	for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line1, $score_min{$timeslot});
		} else {
			push(@line1, "null");
		}
	}
		for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line2, $score_alert{$timeslot});
		} else {
			push(@line2, "null");
		}
	}

}

if ( $graph_name eq "highchart_syncloseddata_sample" ) {


	# Connect to the Sqlite database
	my $dbh = &ConnectToSqlite;

	# Get requested data from the database
	my $sth = $dbh->prepare( "SELECT timestamp,score_min from data WHERE timestamp >= $begin and timestamp <= $end AND type = 3;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_min = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	my @result_tmp = ();
	while ( (@result_tmp ) = $sth->fetchrow()){
		$score_min{$result_tmp[0]} = $result_tmp[1];
	}

	# Get requested data from the database
	my $sth_alert = $dbh->prepare( "SELECT timestamp,score_alert from data WHERE timestamp >= $begin and timestamp <= $end AND type = 3;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth_alert->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_alert = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	@result_tmp = ();
	while ( (@result_tmp ) = $sth_alert->fetchrow()){
		$score_alert{$result_tmp[0]} = $result_tmp[1];
	}

	# Disconnect the Sqlite database
	$dbh->disconnect;

	# Convert requested data to the graph points. It means check if there is
	# data point for each timeslot in given interval and put NULL value instead
	# of missing values 
	@line1 = ();
	@line2 = ();
	for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line1, $score_min{$timeslot});
		} else {
			push(@line1, "null");
		}
	}
		for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line2, $score_alert{$timeslot});
		} else {
			push(@line2, "null");
		}
	}

}
if ( $graph_name eq "highchart_udpdata_sample" ) {


	# Connect to the Sqlite database
	my $dbh = &ConnectToSqlite;

	# Get requested data from the database
	my $sth = $dbh->prepare( "SELECT timestamp,score_min from data WHERE timestamp >= $begin and timestamp <= $end AND type = 2;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_min = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	my @result_tmp = ();
	while ( (@result_tmp ) = $sth->fetchrow()){
		$score_min{$result_tmp[0]} = $result_tmp[1];
	}

	# Get requested data from the database
	my $sth_alert = $dbh->prepare( "SELECT timestamp,score_alert from data WHERE timestamp >= $begin and timestamp <= $end AND type = 2;");
	if ( !defined $sth ) { syslog("info", "helium: Cannot prepare statement: $DBI::errstr\n "); die; }
	$sth_alert->execute or syslog("info", "helium: Cannot execute statement: $DBI::errstr\n ");

	# Prepare hash to store data
	my %score_alert = ();

	# Store data from SQL query to the prepared hash with the timestamp as a key
	@result_tmp = ();
	while ( (@result_tmp ) = $sth_alert->fetchrow()){
		$score_alert{$result_tmp[0]} = $result_tmp[1];
	}

	# Disconnect the Sqlite database
	$dbh->disconnect;

	# Convert requested data to the graph points. It means check if there is
	# data point for each timeslot in given interval and put NULL value instead
	# of missing values 
	@line1 = ();
	@line2 = ();
	for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line1, $score_min{$timeslot});
		} else {
			push(@line1, "null");
		}
	}
		for (my $timeslot = $begin; $timeslot <= $end; $timeslot += 300) {
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timeslot);
		if (exists $score_min{$timeslot}) {
			push(@line2, $score_alert{$timeslot});
		} else {
			push(@line2, "null");
		}
	}

}
	# gather all data into %args hash and send it to the frontend
        my %args;
        $args{"line1"} = \@line1;
        $args{"line2"} = \@line2;
        Nfcomm::socket_send_ok ($socket, \%args);

}

# perl trim function - remove leading and trailing whitespace
sub trim($)
{
  my $string = shift;
  $string =~ s/^\s+//;
  $string =~ s/\s+$//;
  return $string;
}
sub GetSuspicious {

	my $filter = shift (@_);
	my $netflow_sources = shift (@_);
	my $timeslot = shift (@_);
	my $hash = shift (@_);
	
	my @output = `/usr/local/bin/nfdump -q -M $netflow_sources -r nfcapd.$timeslot -o "fmt:\%sa:\%sp:\%dp:" "$filter"`;
	@output = sort @output;

	my $id = 1;
	my $id_alerts = 0;
	my %suspicious = (
			  source_ip => "0",
			  last_source_port => "0",
			  source_port => "0",
			  last_destination_port => "0",
			  destination_port => "0",
			  probes => 1,
			  );
	my $qtde = $#output;
	if ($qtde > 1)
	{

	for my $while_i ( 1 .. $#output ) { # Não colocamos o registro zero pois ele esta vazio
	      # Separa o conteudo da linha em variaveis
	      my $line = $output[$while_i];
	      my ($source_ip, $source_port, $destination_port) = split(/:/,$line);

	      # Limpando variaveis (Retirando espaços e \n)
              $source_ip = trim($source_ip);
              $source_port = trim($source_port);
       	      $destination_port = trim($destination_port);

	      # Verificando se a linha é identica a anterior
	      if ($suspicious{source_ip} eq $source_ip && $suspicious{last_source_port} eq $source_port && $suspicious{last_destination_port} eq $destination_port){
		      ++$suspicious{probes};
	      }else{
		      # Verificando se já existe o registro do IP
		      if ($suspicious{source_ip} eq $source_ip){
		      
			      ++$suspicious{probes};
			      
			      # Verificando se a porta de origem do IP anterior é a mesma, se for, somente a porta de destino pode ter sido alterada
			      if ($suspicious{last_source_port} eq $source_port){
				      # Verifica se já adicionou essa porta
				      unless ($suspicious{destination_port} =~ /\s$destination_port\s/g) { $suspicious{destination_port} .= " $destination_port ";}
				      #$suspicious{destination_port} .= " $destination_port";
				      #say "destination_port not equal";
				      
			      }else{ #  A porta de origem é diferente, vamos verificar se a porta de destino é a mesma
		      
				      # Se a porta de destino for a mesma, acrescentamos somente a porta de origem
				      if ($suspicious{last_destination_port} eq $destination_port){
					      $suspicious{source_port} .= " $source_port";
					      $suspicious{last_source_port} = $source_port;
					      #say "source_port not equal";
					      
					      
				      }else{ # A porta de origem é diferente e a de destino também
				      
					      $suspicious{source_port} .= " $source_port";
					      $suspicious{last_source_port} = $source_port;
					      
					      unless ($suspicious{destination_port} =~ /\s$destination_port\s/g) { $suspicious{destination_port} .= " $destination_port ";}
					      $suspicious{last_destination_port} = $destination_port;
				      }
			      }
		      }else{ # o registro do ip nao existe

			      if ($id != 1){
				      # Se não existe o IP novo, o antigo teve mais um probe que não foi contado
				      ++$suspicious{probes};

				      # Salva a entrada (ip anteror) no array _store_
				      ${$hash}[$id-1]{source_ip} = "'".$suspicious{source_ip}."'";
				      ${$hash}[$id-1]{source_port} = "'".$suspicious{source_port}."'";
				      ${$hash}[$id-1]{destination_port} = "'".$suspicious{destination_port}."'";
				      ${$hash}[$id-1]{probes} = $suspicious{probes};

				      # Reseta o IP (só precisa alterar a variavel probes)
				      $suspicious{probes} = 1;
				      
				      # Incrementa o id para não gravar no mesmo registro
				      ++$id;
			      }
			      
			      ++$id if $id == 1; # Corrige o primeiro registro
			      
			      # Salva o conteudo anterior no hash global
			      %suspicious = (
				      source_ip => $source_ip,
				      last_source_port => $source_port,
				      source_port => $source_port,
				      last_destination_port => $destination_port,
				      destination_port => $destination_port,
			      );
		      
		      }
	      }
	} # Fim do laço while

	} # fim do if qtde > 3
	
	return (\@{$hash});
}

#
# The Init function is called when the plugin is loaded. Its purpose is to give the plugin 
# the possibility to initialize itself. The plugin should return 1 for success or 0 for 
# failure. If the plugin fails to initialize, it's disabled and not used. Therefore, if
# you want to temporarily disable your plugin return 0 when Init is called.
#
sub Init {
	&CreateSqliteTable;
        return 1;
}

1;

#
# The Cleanup function is called, when nfsend terminates. Its purpose is to give the
# plugin the possibility to cleanup itself. Its return value is discarded.
sub Cleanup {
        syslog("info", "anomalia plugin Cleanup");
}

#
# Periodic data processing function
#       input:  hash reference including the items:
#               'profile'       profile name
#               'profilegroup'  profile group
#               'timeslot'      time of slot to process: Format yyyymmddHHMM e.g. 200503031200
sub run {
        # time for profiling purposes
        my $time_start = time();
        my $argref       = shift;

        my $profile      = $$argref{'profile'};
        my $profilegroup = $$argref{'profilegroup'};
        my $timeslot     = $$argref{'timeslot'};

        my %profileinfo     = NfProfile::ReadProfile($profile, $profilegroup);
        my $profilepath     = NfProfile::ProfilePath($profile, $profilegroup);
        my $all_sources     = join ':', keys %{$profileinfo{'channel'}};
        my $netflow_sources = "$NfConf::PROFILEDATADIR/$profilepath/$all_sources";

	# Convert given timeslot into unix timestamp format
	my $year           = substr($timeslot,0,4);
	my $month          = substr($timeslot,4,2);
	my $day            = substr($timeslot,6,2);
	my $hour           = substr($timeslot,8,2);                                               
	my $min            = substr($timeslot,10,2);
	my $timestamp = POSIX::mktime(0,$min,$hour,$day,$month-1,$year-1900);

my %settings = GetSettings();
syslog("info", "anomalia: Settings of SYN: $settings{settings_syn_probes} ");
syslog("info", "anomalia: Settings of SYN Closed: $settings{settings_synclosed_probes} ");
syslog("info", "anomalia: Settings of UDP: $settings{settings_udp_probes} ");
syslog("info", "anomalia: Settings of Null: $settings{settings_null_probes} ");

# Array onde serão gravados os Suspeitos 
my @suspicious_syn = (
	{
	source_ip => "0.0.0.0",
	last_source_port => "0",
	source_port => "0",
	last_destination_port => "0",
	destination_port => "0",
	probes => 1,
	},
);

my @suspicious_udp = (
	{
	source_ip => "0.0.0.0",
	last_source_port => "0",
	source_port => "0",
	last_destination_port => "0",
	destination_port => "0",
	probes => 1,
	},
);

my @suspicious_syn_closed = (
	{
	source_ip => "0.0.0.0",
	last_source_port => "0",
	source_port => "0",
	last_destination_port => "0",
	destination_port => "0",
	probes => 1,
	},
);

my @suspicious_null = (
	{
	source_ip => "0.0.0.0",
	last_source_port => "0",
	source_port => "0",
	last_destination_port => "0",
	destination_port => "0",
	probes => 1,
	},
);

my $syn = "proto tcp and (src port > 1023 and dst port in [ \@include /usr/local/lib/nfsen/plugins/anomalia/portas_origem.txt ]) and flags S and not flags RAFPU";
my $udp = "proto udp and (src port > 1023 and dst port in [ \@include /usr/local/lib/nfsen/plugins/anomalia/portas_origem.txt ])";
my $syn_closed = "proto tcp and (dst port in [ \@include /usr/local/lib/nfsen/plugins/anomalia/portas_origem.txt ]) and flags RA and not flags FPU";
my $null = "proto tcp and (dst port in [ \@include /usr/local/lib/nfsen/plugins/anomalia/portas_origem.txt ]) and flags R and not flags PUSFA and packets = 1";

#my $syn = "proto tcp and src port > 1023 and flags S and not flags RAFPU";
#my $udp = "proto udp and src port > 1023";
#my $syn_closed = "proto tcp and flags RA and not flags FPU";
#my $null = "proto tcp and flags R and not flags PUSFA and packets = 1";

GetSuspicious($syn,$netflow_sources,$timeslot,\@suspicious_syn);
syslog("info", "anomalia: GetSuspicious SYN success");
        
GetSuspicious($udp,$netflow_sources,$timeslot,\@suspicious_udp);
syslog("info", "anomalia: GetSuspicious UDP success");

GetSuspicious($syn_closed,$netflow_sources,$timeslot,\@suspicious_syn_closed);
syslog("info", "anomalia: GetSuspicious SYN CLOSED success");

GetSuspicious($null,$netflow_sources,$timeslot,\@suspicious_null);
syslog("info", "anomalia: GetSuspicious NULL success");

# Save processed data into SQLite database
my $dbh = &ConnectToSqlite;

if ($#suspicious_syn > 1){
	my $score_alert = 0;
	for my $i ( 1 .. $#suspicious_syn ) { # Não colocamos o registro zero pois ele esta vazio
	    if ($suspicious_syn[$i]{probes} >= $settings{settings_syn_probes}){ 
		my $sth = $dbh->prepare( "INSERT INTO SuspiciousSyn (timestamp,source_ip,source_port,destination_port,probes ) values ($timestamp,$suspicious_syn[$i]{source_ip},$suspicious_syn[$i]{source_port},$suspicious_syn[$i]{destination_port},$suspicious_syn[$i]{probes}, );");
		if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
		++$score_alert;
	    }
	}
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,0,$#suspicious_syn,$score_alert );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

} else {
	my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,0,0,0 );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

}

if ($#suspicious_syn_closed > 1){
	my $score_alert = 0;
	for my $i ( 1 .. $#suspicious_syn_closed ) { # Não colocamos o registro zero pois ele esta vazio
	    if ($suspicious_syn_closed[$i]{probes} >= $settings{settings_synclosed_probes}){
		my $sth = $dbh->prepare( "INSERT INTO SuspiciousSynClosed (timestamp,source_ip,source_port,destination_port,probes ) values ($timestamp,$suspicious_syn_closed[$i]{source_ip},$suspicious_syn_closed[$i]{source_port},$suspicious_syn_closed[$i]{destination_port},$suspicious_syn_closed[$i]{probes}, );");
		if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
		++$score_alert;
	    }
	}
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,1,$#suspicious_syn_closed,$score_alert );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

} else {
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,1,0,0 );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

}

if ($#suspicious_udp > 1){
	my $score_alert = 0; 
	for my $i ( 1 .. $#suspicious_udp ) { # Não colocamos o registro zero pois ele esta vazio
	    if ($suspicious_udp[$i]{probes} >= $settings{settings_udp_probes}){
		my $sth = $dbh->prepare( "INSERT INTO SuspiciousUdp (timestamp,source_ip,source_port,destination_port,probes ) values ($timestamp,$suspicious_udp[$i]{source_ip},$suspicious_udp[$i]{source_port},$suspicious_udp[$i]{destination_port},$suspicious_udp[$i]{probes} );");
		 #print "INSERT INTO SuspiciousUdp (timestamp,source_ip,source_port,destination_port,probes ) values ($timestamp,$suspicious_udp[$i]{source_ip},$suspicious_udp[$i]{source_port},$suspicious_udp[$i]{destination_port},$suspicious_udp[$i]{probes} );";
		if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
		++$score_alert;
	    }
	}
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,2,$#suspicious_udp,$score_alert );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

} else {
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,2,0,0 );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

}

if ($#suspicious_null > 1){
	my $score_alert = 0;
	for my $i ( 1 .. $#suspicious_null ) { # Não colocamos o registro zero pois ele esta vazio
	    if ($suspicious_null[$i]{probes} >= $settings{settings_null_probes}){
		my $sth = $dbh->prepare( "INSERT INTO SuspiciousNull (timestamp,source_ip,source_port,destination_port,probes ) values ($timestamp,$suspicious_null[$i]{source_ip},$suspicious_null[$i]{source_port},$suspicious_null[$i]{destination_port},$suspicious_null[$i]{probes}, );");
		if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
		$sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");
		++$score_alert;
	    }
	}
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,3,$#suspicious_syn,$score_alert );");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

} else {
        my $sth = $dbh->prepare( "INSERT INTO data (timestamp,type,score_min,score_alert ) values ($timestamp,3,0,0);");
        if ( !defined $sth ) { die "Cannot prepare statement: $DBI::errstr\n"; }
        $sth->execute or syslog("info", "anomalia: Cannot execute statement: $DBI::errstr\n ");

}

$dbh->disconnect;
	
	# end of "run" function print the running time into syslog
        my $time_stop = time();
        syslog("info", "anomalia: run method ended at " . scalar localtime ($time_stop) . ", duration " . ($time_stop - $time_start) . " seconds.");

	return 1;

} # End of run
