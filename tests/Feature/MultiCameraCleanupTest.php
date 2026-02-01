<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCameraCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Rugby Club',
            'slug' => 'test-club',
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Attach user to organization
        $this->user->organizations()->attach($this->organization, [
            'role' => 'analista',
        ]);

        // Create test category
        $this->category = Category::factory()->create([
            'name' => 'Primera División',
            'organization_id' => $this->organization->id,
        ]);
    }

    /**
     * Test that deleting a SLAVE video removes it from group but keeps the group
     */
    public function test_deleting_slave_video_removes_from_group_but_keeps_group(): void
    {
        // Create master video
        $master = Video::factory()->create([
            'title' => 'Master Video',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create 2 slave videos
        $slave1 = Video::factory()->create([
            'title' => 'Slave 1',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        $slave2 = Video::factory()->create([
            'title' => 'Slave 2',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create video group
        $group = VideoGroup::create([
            'name' => 'Test Multi-Camera Group',
            'organization_id' => $this->organization->id,
        ]);

        // Attach master
        $group->videos()->attach($master->id, [
            'is_master' => true,
            'camera_angle' => 'Master',
            'is_synced' => true,
            'sync_offset' => 0,
        ]);

        // Attach slaves
        $group->videos()->attach($slave1->id, [
            'is_master' => false,
            'camera_angle' => 'Lateral Derecha',
            'is_synced' => true,
            'sync_offset' => 2.5,
        ]);

        $group->videos()->attach($slave2->id, [
            'is_master' => false,
            'camera_angle' => 'Try Zone',
            'is_synced' => false,
            'sync_offset' => null,
        ]);

        // Verify initial state
        $this->assertEquals(3, $group->videos()->count());
        $this->assertTrue($group->exists);

        // Delete slave1
        $slave1->delete();

        // Refresh group
        $group->refresh();

        // Verify group still exists
        $this->assertTrue($group->exists);

        // Verify group now has 2 videos (master + slave2)
        $this->assertEquals(2, $group->videos()->count());

        // Verify master and slave2 are still there
        $this->assertTrue($group->videos()->where('videos.id', $master->id)->exists());
        $this->assertTrue($group->videos()->where('videos.id', $slave2->id)->exists());

        // Verify slave1 is gone from pivot table
        $this->assertFalse($group->videos()->where('videos.id', $slave1->id)->exists());

        // Verify slave1 is deleted from videos table
        $this->assertDatabaseMissing('videos', ['id' => $slave1->id]);
    }

    /**
     * Test that deleting MASTER video dissolves the entire group
     */
    public function test_deleting_master_video_dissolves_entire_group(): void
    {
        // Create master video
        $master = Video::factory()->create([
            'title' => 'Master Video',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create 2 slave videos
        $slave1 = Video::factory()->create([
            'title' => 'Slave 1',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        $slave2 = Video::factory()->create([
            'title' => 'Slave 2',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create video group
        $group = VideoGroup::create([
            'name' => 'Test Multi-Camera Group',
            'organization_id' => $this->organization->id,
        ]);

        $groupId = $group->id;

        // Attach master
        $group->videos()->attach($master->id, [
            'is_master' => true,
            'camera_angle' => 'Master',
            'is_synced' => true,
            'sync_offset' => 0,
        ]);

        // Attach slaves
        $group->videos()->attach($slave1->id, [
            'is_master' => false,
            'camera_angle' => 'Lateral Derecha',
            'is_synced' => true,
            'sync_offset' => 2.5,
        ]);

        $group->videos()->attach($slave2->id, [
            'is_master' => false,
            'camera_angle' => 'Try Zone',
            'is_synced' => false,
            'sync_offset' => null,
        ]);

        // Verify initial state
        $this->assertEquals(3, $group->videos()->count());

        // Delete master
        $master->delete();

        // Verify group is deleted
        $this->assertDatabaseMissing('video_groups', ['id' => $groupId]);

        // Verify all pivot entries are deleted
        $this->assertDatabaseMissing('video_group_video', ['video_group_id' => $groupId]);

        // Verify slave videos still exist (not cascade deleted)
        $this->assertDatabaseHas('videos', ['id' => $slave1->id]);
        $this->assertDatabaseHas('videos', ['id' => $slave2->id]);

        // Verify master is deleted
        $this->assertDatabaseMissing('videos', ['id' => $master->id]);
    }

    /**
     * Test that deleting a video in multiple groups only affects those groups appropriately
     */
    public function test_deleting_video_in_multiple_groups_handles_each_group_correctly(): void
    {
        // Create videos
        $video1 = Video::factory()->create([
            'title' => 'Video 1',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        $video2 = Video::factory()->create([
            'title' => 'Video 2',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        $video3 = Video::factory()->create([
            'title' => 'Video 3',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        $video4 = Video::factory()->create([
            'title' => 'Video 4',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create Group 1: video1 (master), video2 (slave)
        $group1 = VideoGroup::create([
            'name' => 'Group 1',
            'organization_id' => $this->organization->id,
        ]);

        $group1->videos()->attach($video1->id, [
            'is_master' => true,
            'camera_angle' => 'Master',
            'is_synced' => true,
            'sync_offset' => 0,
        ]);

        $group1->videos()->attach($video2->id, [
            'is_master' => false,
            'camera_angle' => 'Slave',
            'is_synced' => true,
            'sync_offset' => 1.0,
        ]);

        // Create Group 2: video3 (master), video2 (slave - same video in different group!)
        $group2 = VideoGroup::create([
            'name' => 'Group 2',
            'organization_id' => $this->organization->id,
        ]);

        $group2->videos()->attach($video3->id, [
            'is_master' => true,
            'camera_angle' => 'Master',
            'is_synced' => true,
            'sync_offset' => 0,
        ]);

        $group2->videos()->attach($video2->id, [
            'is_master' => false,
            'camera_angle' => 'Angle 2',
            'is_synced' => false,
            'sync_offset' => null,
        ]);

        $group2->videos()->attach($video4->id, [
            'is_master' => false,
            'camera_angle' => 'Angle 3',
            'is_synced' => true,
            'sync_offset' => 3.0,
        ]);

        // Verify initial state
        $this->assertEquals(2, $group1->videos()->count());
        $this->assertEquals(3, $group2->videos()->count());

        // Delete video2 (slave in both groups)
        $video2->delete();

        // Refresh groups
        $group1->refresh();
        $group2->refresh();

        // Both groups should still exist
        $this->assertTrue($group1->exists);
        $this->assertTrue($group2->exists);

        // Group 1 should have 1 video (master only)
        $this->assertEquals(1, $group1->videos()->count());
        $this->assertTrue($group1->videos()->where('videos.id', $video1->id)->exists());

        // Group 2 should have 2 videos (master + video4)
        $this->assertEquals(2, $group2->videos()->count());
        $this->assertTrue($group2->videos()->where('videos.id', $video3->id)->exists());
        $this->assertTrue($group2->videos()->where('videos.id', $video4->id)->exists());
    }

    /**
     * Test defensive filtering in controller when orphaned references exist
     */
    public function test_controller_handles_orphaned_master_gracefully(): void
    {
        // Create master video
        $master = Video::factory()->create([
            'title' => 'Master Video',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create slave video
        $slave = Video::factory()->create([
            'title' => 'Slave Video',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create group
        $group = VideoGroup::create([
            'name' => 'Test Group',
            'organization_id' => $this->organization->id,
        ]);

        $group->videos()->attach($master->id, [
            'is_master' => true,
            'camera_angle' => 'Master',
            'is_synced' => true,
            'sync_offset' => 0,
        ]);

        $group->videos()->attach($slave->id, [
            'is_master' => false,
            'camera_angle' => 'Slave',
            'is_synced' => true,
            'sync_offset' => 2.0,
        ]);

        // Manually delete master without triggering observer (simulate orphaned state)
        $master->videoGroups()->detach();
        $master->forceDelete();

        // Try to load angles via controller
        $response = $this->actingAs($this->user)
            ->getJson("/videos/{$slave->id}/multi-camera/angles?group_id={$group->id}");

        // Should return 404 with should_reload flag
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'should_reload' => true,
        ]);

        // Verify group was dissolved
        $this->assertDatabaseMissing('video_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('video_group_video', ['video_group_id' => $group->id]);
    }

    /**
     * Test that no cleanup happens when video is not part of any group
     */
    public function test_deleting_video_not_in_group_does_nothing(): void
    {
        // Create standalone video
        $video = Video::factory()->create([
            'title' => 'Standalone Video',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Delete video
        $video->delete();

        // Verify video is deleted
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);

        // No errors should occur (observer should handle gracefully)
        $this->assertTrue(true);
    }
}
