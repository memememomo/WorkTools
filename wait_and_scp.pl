use strict;
use warnings;
use Filesys::Notify::Simple;
use File::Basename;
use File::Spec;

my $base = File::Spec->rel2abs( dirname(__FILE__) );
my $remote_root = 'path_to_remote';
my @dirs = ('.');


@dirs = map { $base . '/' . $_ } @dirs;
my $watcher = Filesys::Notify::Simple->new(\@dirs);


while (1) {
    $watcher->wait(
	sub {
	    for my $event (@_) {
		my $path = $event->{path};
		if ( -f $path ) {
		    $path =~ s#^$base/##;
		    system("scp $path $remote_root/$path");
		}
	    }
	}
    );
}
