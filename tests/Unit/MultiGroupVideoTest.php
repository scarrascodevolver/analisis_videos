<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Video;
use App\Models\VideoGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiGroupVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_can_belong_to_multiple_groups()
    {
        $org = Organization::factory()->create();
        $video = Video::factory()->create(['organization_id' => $org->id]);

        $group1 = VideoGroup::create(['name' => 'Group 1', 'organization_id' => $org->id]);
        $group2 = VideoGroup::create(['name' => 'Group 2', 'organization_id' => $org->id]);

        $group1->videos()->attach($video->id, ['is_master' => false, 'camera_angle' => 'Angle 1']);
        $group2->videos()->attach($video->id, ['is_master' => false, 'camera_angle' => 'Angle 2']);

        $this->assertEquals(2, $video->videoGroups()->count());
        $this->assertTrue($video->isInGroup($group1->id));
        $this->assertTrue($video->isInGroup($group2->id));
    }

    public function test_video_can_be_master_in_one_group_and_slave_in_another()
    {
        $org = Organization::factory()->create();
        $video = Video::factory()->create(['organization_id' => $org->id]);

        $group1 = VideoGroup::create(['name' => 'Group 1', 'organization_id' => $org->id]);
        $group2 = VideoGroup::create(['name' => 'Group 2', 'organization_id' => $org->id]);

        $group1->videos()->attach($video->id, ['is_master' => true, 'camera_angle' => 'Master']);
        $group2->videos()->attach($video->id, ['is_master' => false, 'camera_angle' => 'Slave']);

        $this->assertTrue($video->isMasterInGroup($group1->id));
        $this->assertFalse($video->isMasterInGroup($group2->id));
        $this->assertTrue($video->isSlave($group2->id));
        $this->assertFalse($video->isSlave($group1->id));
    }

    public function test_video_can_have_different_sync_offset_per_group()
    {
        $org = Organization::factory()->create();
        $video = Video::factory()->create(['organization_id' => $org->id]);

        $group1 = VideoGroup::create(['name' => 'Group 1', 'organization_id' => $org->id]);
        $group2 = VideoGroup::create(['name' => 'Group 2', 'organization_id' => $org->id]);

        $group1->videos()->attach($video->id, [
            'is_master' => false,
            'camera_angle' => 'Angle 1',
            'sync_offset' => 10.5,
            'is_synced' => true,
        ]);

        $group2->videos()->attach($video->id, [
            'is_master' => false,
            'camera_angle' => 'Angle 2',
            'sync_offset' => -5.2,
            'is_synced' => true,
        ]);

        $pivot1 = $video->videoGroups()->where('video_groups.id', $group1->id)->first()->pivot;
        $pivot2 = $video->videoGroups()->where('video_groups.id', $group2->id)->first()->pivot;

        $this->assertEquals(10.5, $pivot1->sync_offset);
        $this->assertEquals(-5.2, $pivot2->sync_offset);
    }

    public function test_remove_video_from_specific_group_keeps_other_groups()
    {
        $org = Organization::factory()->create();
        $video = Video::factory()->create(['organization_id' => $org->id]);

        $group1 = VideoGroup::create(['name' => 'Group 1', 'organization_id' => $org->id]);
        $group2 = VideoGroup::create(['name' => 'Group 2', 'organization_id' => $org->id]);

        $group1->videos()->attach($video->id, ['is_master' => false, 'camera_angle' => 'Angle 1']);
        $group2->videos()->attach($video->id, ['is_master' => false, 'camera_angle' => 'Angle 2']);

        $video->removeFromGroup($group1->id);

        $this->assertFalse($video->isInGroup($group1->id));
        $this->assertTrue($video->isInGroup($group2->id));
        $this->assertEquals(1, $video->videoGroups()->count());
    }

    public function test_video_group_model_returns_correct_master_and_slaves()
    {
        $org = Organization::factory()->create();
        $master = Video::factory()->create(['organization_id' => $org->id]);
        $slave1 = Video::factory()->create(['organization_id' => $org->id]);
        $slave2 = Video::factory()->create(['organization_id' => $org->id]);

        $group = VideoGroup::create(['name' => 'Test Group', 'organization_id' => $org->id]);

        $group->videos()->attach($master->id, ['is_master' => true, 'camera_angle' => 'Master']);
        $group->videos()->attach($slave1->id, ['is_master' => false, 'camera_angle' => 'Slave 1']);
        $group->videos()->attach($slave2->id, ['is_master' => false, 'camera_angle' => 'Slave 2']);

        $this->assertEquals($master->id, $group->getMasterVideo()->id);
        $this->assertEquals(2, $group->getSlaveVideos()->count());
    }

    public function test_associate_to_master_creates_group_if_needed()
    {
        $org = Organization::factory()->create();
        $master = Video::factory()->create(['organization_id' => $org->id]);
        $slave = Video::factory()->create(['organization_id' => $org->id]);

        $result = $slave->associateToMaster($master, 'Lateral');

        $this->assertTrue($result);
        $this->assertEquals(1, VideoGroup::count());
        $this->assertTrue($master->isPartOfGroup());
        $this->assertTrue($slave->isPartOfGroup());
    }

    public function test_sync_with_master_updates_pivot_for_specific_group()
    {
        $org = Organization::factory()->create();
        $video = Video::factory()->create(['organization_id' => $org->id]);

        $group = VideoGroup::create(['name' => 'Test Group', 'organization_id' => $org->id]);
        $group->videos()->attach($video->id, [
            'is_master' => false,
            'camera_angle' => 'Test Angle',
            'is_synced' => false,
        ]);

        $result = $video->syncWithMaster(15.5, 'Kickoff', $group->id);

        $this->assertTrue($result);
        $pivot = $video->videoGroups()->where('video_groups.id', $group->id)->first()->pivot;
        $this->assertEquals(15.5, $pivot->sync_offset);
        $this->assertTrue($pivot->is_synced);
        $this->assertEquals('Kickoff', $pivot->sync_reference_event);
    }
}
