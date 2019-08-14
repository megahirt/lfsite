import * as angular from 'angular';
import { ProjectService } from '../../../../bellows/core/api/project.service';
import { Session } from '../../../../bellows/core/session.service';
import { UtilityService } from '../../../../bellows/core/utility.service';
import { Project } from '../../../../bellows/shared/model/project.model';
import { User } from '../../../../bellows/shared/model/user.model';
import { Permission } from './permissions-dropdown.component';

export interface SpecialPermissionTargets {
  anonymousUser: {role: string};
  reusableInviteLinkUser: {role: string};
}

export class UserManagementController implements angular.IController {
  getAvatarUrl = UtilityService.getAvatarUrl;
  allMembers: User[];
  visibleMembers: Array<Partial<User>>;
  userFilter: string = '';
  projectUrl = 'http://languageforge.org/app/lexicon/real_project_url';
  project: Project;
  session: Session;
  specialPermissionTargets: SpecialPermissionTargets;

  static $inject = ['$q', 'projectService', 'sessionService'];
  constructor(
    private readonly $q: angular.IQService,
    private readonly projectService: ProjectService) { }

  $onInit(): void {
    // TODO: actually hook anonymousUserRole up to the backend
    this.project.anonymousUserRole = 'disabled';
    this.specialPermissionTargets = {
      anonymousUser: {role: this.project.anonymousUserRole},
      reusableInviteLinkUser: {role: this.project.reusableInviteLinkRole}
    };
  }

  userisCurrentUser(user: User) {
    return this.session.data.username === user.username;
  }

  userIsOwner(user: User) {
    return user.id === this.project.ownerRef.id;
  }

  onPermissionChanged($event: {permission: Permission, target: any}) {
    $event.target.role = $event.permission.name;
  }

  loadMemberData(): angular.IPromise<void> {
    return this.projectService.listUsers().then( result => {
      if (result.ok) {
        // include invitees in the list of members
        this.allMembers = result.data.users.concat(result.data.invitees.map((invitee: User) => {
          invitee.isInvitee = true;
          return invitee;
        }));

        // set the avatar_url for convenient use in the template
        for (let i = 0; i < this.allMembers.length; i++) {
          this.allMembers[i].avatar_ref = this.allMembers[i].avatar_ref || 'anonymoose.png';
          this.allMembers[i].avatarUrl = this.getAvatarUrl(this.allMembers[i].avatar_ref);
        }
      }
    });
  }

  onSendEmailInvite($event: { email: string, role: string }) {
    // TODO: actually send the email
    const freshInviteUser: Partial<User> = {
      email: $event.email,
      role: $event.role,
      isInvitee: true,
      avatar_ref: 'anonymoose.png'
    };
    this.visibleMembers.push(freshInviteUser);
  }

  removeUser(user: User) {
    const index = this.visibleMembers.indexOf(user);
    this.visibleMembers.splice(index, 1);
    // TODO: actually remove the user from the project
  }

  onDeleteTarget($event: { target: any }) {
    if (($event.target as User).avatar_ref !== undefined) { // target is a User
      this.removeUser($event.target);
    }
  }

}

export const UserManagementComponent: angular.IComponentOptions = {
  bindings: {
    project: '<',
    session: '<'
  },
  controller: UserManagementController,
  templateUrl: '/angular-app/languageforge/lexicon/shared/share-with-others/user-management.component.html'
};
