
	public function testBackUpDirIsExcludedWhenBackUpDirIsNotInRoot() {

		$this->assertNotContains( Path::get_root(), Path::get_path() );
		$this->assertEmpty( $this->backup->get_excludes() );

	}

	public function testBackUpDirIsExcludedWhenBackUpDirIsInRoot() {

		Path::get_instance()->set_path( dirname( dirname( __FILE__ ) ) . '/test-data/tmp' );

		$this->assertContains( Path::get_root(), Path::get_path() );
		$this->assertNotEmpty( $this->backup->get_excludes() );
		$this->assertContains( trailingslashit( Path::get_path() ), $this->backup->get_excludes() );

	}

	public function testExcludeAbsoluteDirPath() {

		$this->backup->set_excludes( '/exclude/' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteRootDirPath() {

		$this->backup->set_excludes( dirname( dirname( __FILE__ ) ) . '/test-data/exclude/' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeDirPathFragment() {

		$this->backup->set_excludes( 'exclude/' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsoluteDirPath() {

		$this->backup->set_excludes( 'exclude' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteFilePath() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );

        $this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsoluteFilePath() {

		$this->backup->set_excludes( 'exclude/exclude.exclude' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( '/exclude/*' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( 'exclude/*' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileName() {

		$this->backup->set_excludes( '*.exclude' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( '/exclude/*.exclude' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( 'exclude/*.exclude' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileExtension() {

		$this->backup->set_excludes( 'exclude.*' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( '/exclude/exclude.*' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( 'exclude/exclude.*' );

		$this->backup->backup();

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testWildCard() {

		$this->backup->set_excludes( '*' );

		$this->backup->backup();

		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 0 );

		// Expect an error "Nothing to do"
		$this->assertNotEmpty( $this->backup->get_warnings() );

	}